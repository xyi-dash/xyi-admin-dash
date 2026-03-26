<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenForoService
{
    private string $apiUrl;
    private string $apiKey;
    private int $blacklistThreadId;
    private int $firstPostId = 196656; // ID первого поста темы

    public function __construct()
    {
        $this->apiUrl = config('services.xenforo.api_url');
        $this->apiKey = config('services.xenforo.api_key');
        $this->blacklistThreadId = config('services.xenforo.blacklist_thread_id');
    }

    public function addToBlacklist(array $data): array
    {
        try {
            Log::info('XenForo Blacklist - Starting addToBlacklist', [
                'first_post_id' => $this->firstPostId,
            ]);
            
            // Получить текущее содержимое первого поста
            $currentContent = $this->getPostContent($this->firstPostId);
            
            if (empty($currentContent)) {
                Log::error('XenForo Blacklist - Failed to get post content');
                return [
                    'success' => false,
                    'error' => 'Не удалось получить содержимое поста',
                ];
            }
            
            Log::info('XenForo Blacklist - Got post content', [
                'content_length' => strlen($currentContent),
            ]);
            
            // Определить категорию по первому символу никнейма
            $nickname = $data['nickname'];
            $category = $this->getCategory($nickname);
            
            Log::info('XenForo Blacklist - Determined category', [
                'nickname' => $nickname,
                'category' => $category,
            ]);
            
            // Форматировать новую запись
            $newEntry = $this->formatBlacklistEntry($data);
            
            Log::info('XenForo Blacklist - Formatted entry', [
                'entry_length' => strlen($newEntry),
            ]);
            
            // Вставить запись в правильную категорию
            $updatedContent = $this->insertEntryIntoCategory($currentContent, $newEntry, $category, $nickname);
            
            Log::info('XenForo Blacklist - Inserted entry into category', [
                'updated_content_length' => strlen($updatedContent),
            ]);
            
            // Обновить пост
            $result = $this->updatePost($this->firstPostId, $updatedContent);
            
            if ($result) {
                Log::info('XenForo Blacklist - Successfully updated post', [
                    'post_id' => $this->firstPostId,
                ]);
                return [
                    'success' => true,
                    'post_id' => $this->firstPostId,
                    'message' => 'Запись добавлена в черный список на форуме',
                ];
            }
            
            Log::error('XenForo Blacklist - Failed to update post');
            return [
                'success' => false,
                'error' => 'Не удалось обновить пост',
            ];
            
        } catch (\Exception $e) {
            Log::error('XenForo API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Не удалось подключиться к форуму',
            ];
        }
    }
    
    private function getCategory(string $nickname): string
    {
        $firstChar = mb_substr($nickname, 0, 1);
        $firstCharUpper = mb_strtoupper($firstChar);
        
        // Проверить, является ли первый символ цифрой
        if (preg_match('/[0-9]/', $firstChar)) {
            return '0';
        }
        
        // Проверить, является ли первый символ латинской буквой A-Z
        if (preg_match('/[A-Z]/i', $firstChar)) {
            return strtoupper($firstChar);
        }
        
        // Для кириллицы и других символов - транслитерация или OTHER
        // Попробуем транслитерировать кириллицу в латиницу
        $translitMap = [
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Z', 'З' => 'Z', 'И' => 'I',
            'Й' => 'I', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'C',
            'Ш' => 'S', 'Щ' => 'S', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'U', 'Я' => 'Y',
        ];
        
        if (isset($translitMap[$firstCharUpper])) {
            $translitChar = $translitMap[$firstCharUpper];
            return $translitChar !== '' ? $translitChar : 'OTHER';
        }
        
        // Для всех остальных символов
        return 'OTHER';
    }
    
    private function getPostContent(int $postId): string
    {
        $response = Http::withHeaders([
            'XF-Api-Key' => $this->apiKey,
        ])->get("{$this->apiUrl}/posts/{$postId}");
        
        if ($response->successful()) {
            return $response->json('post.message', '');
        }
        
        Log::error('Failed to get post content', [
            'post_id' => $postId,
            'status' => $response->status(),
        ]);
        
        return '';
    }
    
    private function insertEntryIntoCategory(string $content, string $newEntry, string $category, string $nickname): string
    {
        // Найти секцию категории
        $categoryHeader = "{" . $category . "}";
        
        // Разбить контент на строки
        $lines = explode("\n", $content);
        
        // Найти начало и конец секции категории
        $categoryStartIndex = -1;
        $categoryEndIndex = -1;
        
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Найти начало нужной категории (точное совпадение)
            if ($categoryStartIndex === -1 && $line === $categoryHeader) {
                $categoryStartIndex = $i;
                continue;
            }
            
            // Найти конец категории (следующая категория или конец файла)
            if ($categoryStartIndex !== -1 && $categoryEndIndex === -1) {
                // Проверить, является ли строка заголовком другой категории
                if (preg_match('/^\{[A-Z0-9]+\}$/', $line) || preg_match('/^\{OTHER\}$/', $line)) {
                    $categoryEndIndex = $i;
                    break;
                }
            }
        }
        
        // Если категория не найдена, добавить в конец
        if ($categoryStartIndex === -1) {
            return $content . "\n\n" . $categoryHeader . "\n" . $newEntry;
        }
        
        // Если конец не найден, значит это последняя категория
        if ($categoryEndIndex === -1) {
            $categoryEndIndex = count($lines);
        }
        
        // Извлечь записи из категории
        $categoryEntries = [];
        for ($i = $categoryStartIndex + 1; $i < $categoryEndIndex; $i++) {
            $line = $lines[$i];
            // Пропустить пустые строки
            if (trim($line) !== '') {
                $categoryEntries[] = $line;
            }
        }
        
        // Добавить новую запись
        $categoryEntries[] = $newEntry;
        
        // Отсортировать записи по никнейму (извлечь из [B]nickname[/B])
        usort($categoryEntries, function($a, $b) {
            // Извлечь никнейм из [B]nickname[/B]
            preg_match('/\[B\]([^\[]+)\[\/B\]/', $a, $matchesA);
            preg_match('/\[B\]([^\[]+)\[\/B\]/', $b, $matchesB);
            
            $nicknameA = $matchesA[1] ?? '';
            $nicknameB = $matchesB[1] ?? '';
            
            return strcasecmp($nicknameA, $nicknameB);
        });
        
        // Собрать контент обратно
        $newLines = array_slice($lines, 0, $categoryStartIndex + 1);
        
        // Добавить пустую строку после заголовка категории
        $newLines[] = '';
        
        // Добавить отсортированные записи
        foreach ($categoryEntries as $entry) {
            $newLines[] = $entry;
        }
        
        // Добавить пустую строку перед следующей категорией
        if ($categoryEndIndex < count($lines)) {
            $newLines[] = '';
            $newLines = array_merge($newLines, array_slice($lines, $categoryEndIndex));
        }
        
        return implode("\n", $newLines);
    }
    
    private function updatePost(int $postId, string $message): bool
    {
        $response = Http::withHeaders([
            'XF-Api-Key' => $this->apiKey,
        ])->post("{$this->apiUrl}/posts/{$postId}", [
            'message' => $message,
        ]);
        
        Log::info('XenForo update post response', [
            'post_id' => $postId,
            'status' => $response->status(),
            'success' => $response->successful(),
        ]);
        
        return $response->successful();
    }

    private function formatBlacklistEntry(array $data): string
    {
        $nickname = $data['nickname'];
        $accountId = $data['account_id'];
        $server = $this->formatServer($data['server']);
        $reason = $data['reason'];
        $lastIp = $data['last_ip'] ?? '-';
        $regIp = $data['reg_ip'] ?? '-';
        $lastHash = $data['last_hash'] ?? '-';
        $vkLink = $data['vk_link'] ?? '';
        $forumAccount = $data['forum_account'] ?? '';
        $discordLogin = $data['discord_login'] ?? '';
        $screenshot = $data['screenshot'] ?? '';
        $proofs = $data['proofs'] ?? '';
        $date = now()->format('d.m.y, H:i');

        // Основная строка
        $entry = "[B]{$nickname}[/B] ({$accountId}) [{$server}] - {$reason}";
        $entry .= " | IP {$lastIp} / RIP {$regIp}";
        
        if ($lastHash !== '-' && !empty($lastHash)) {
            $entry .= " | {$lastHash}";
        }
        
        if ($vkLink) {
            $entry .= " | [URL='{$vkLink}']VK[/URL]";
        }
        
        $entry .= " | {$date}";

        // Дополнительные поля (если есть)
        $additional = [];
        if ($forumAccount) {
            $additional[] = "Форумный аккаунт: {$forumAccount}";
        }
        if ($discordLogin) {
            $additional[] = "Логин Discord: {$discordLogin}";
        }
        if ($screenshot) {
            $additional[] = "Скриншот статистики: {$screenshot}";
        }
        if ($proofs) {
            $additional[] = "Доказательства: {$proofs}";
        }

        if (!empty($additional)) {
            $entry .= "\n" . implode("\n", $additional);
        }

        return $entry;
    }

    private function formatServer(string $server): string
    {
        return match ($server) {
            'one' => 'One',
            'two' => 'Two',
            'three' => 'Three',
            default => ucfirst($server),
        };
    }
}
