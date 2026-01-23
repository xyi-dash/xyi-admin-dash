<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NewsService
{
    public function getAll(): array
    {
        return DB::connection('site')
            ->table('news')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'title' => $row->title,
                'date' => $row->date ?? $row->date2 ?? null,
                'author' => $row->kem ?? null,
                'message' => $row->message,
                'message2' => $row->message2 ?? null,
            ])
            ->toArray();
    }

    public function getById(int $id): ?array
    {
        $row = DB::connection('site')
            ->table('news')
            ->where('id', $id)
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id,
            'title' => $row->title,
            'date' => $row->date ?? $row->date2 ?? null,
            'author' => $row->kem ?? null,
            'message' => $row->message,
            'message2' => $row->message2 ?? null,
        ];
    }

    public function create(string $title, string $message, string $message2, string $author): int
    {
        $date = 'Дата добавления: '.now()->format('d.m.Y');
        $date2 = now()->format('d.m.Y');

        return DB::connection('site')
            ->table('news')
            ->insertGetId([
                'title' => $title,
                'date' => $date,
                'date2' => $date2,
                'message' => $message,
                'message2' => $message2,
                'kem' => $author,
            ]);
    }

    public function update(int $id, string $title, string $message, string $message2): bool
    {
        return DB::connection('site')
            ->table('news')
            ->where('id', $id)
            ->update([
                'title' => $title,
                'message' => $message,
                'message2' => $message2,
            ]) !== false;
    }

    public function delete(int $id): bool
    {
        return DB::connection('site')
            ->table('news')
            ->where('id', $id)
            ->delete() > 0;
    }
}
