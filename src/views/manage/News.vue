<script setup>
import api from '@/service/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { onMounted, ref } from 'vue';

const { t } = useI18n();

const toast = useToast();
const confirm = useConfirm();

const loading = ref(false);
const data = ref([]);
const showDialog = ref(false);
const saving = ref(false);
const form = ref({
    id: null,
    title: '',
    message: '',
    message2: ''
});

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const response = await api.get('/admin/news');
        data.value = response.data.news || [];
    } catch (error) {
        console.warn("no news is good news. or is it?... wait, actually no, that's weird take.");
    } finally {
        loading.value = false;
    }
}

function openNew() {
    form.value = { id: null, title: '', message: '', message2: '' };
    showDialog.value = true;
}

function editNews(item) {
    form.value = { ...item };
    showDialog.value = true;
}

async function saveNews() {
    if (!form.value.title) {
        toast.add({ severity: 'warn', summary: t('common.warning'), detail: t('news.title_required'), life: 3000 });
        return;
    }

    saving.value = true;
    try {
        if (form.value.id) {
            await api.put(`/admin/news/${form.value.id}`, form.value);
        } else {
            await api.post('/admin/news', form.value);
        }

        toast.add({ severity: 'success', summary: t('common.success'), detail: t('news.saved'), life: 3000 });
        showDialog.value = false;
        await loadData();
    } catch (error) {
        toast.add({ severity: 'error', summary: t('common.error'), detail: t('news.save_failed'), life: 3000 });
    } finally {
        saving.value = false;
    }
}

function confirmDelete(item) {
    confirm.require({
        message: t('news.delete_confirm', { title: item.title }),
        header: t('news.confirm_delete'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => deleteNews(item.id)
    });
}

async function deleteNews(id) {
    try {
        await api.delete(`/admin/news/${id}`);
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('news.deleted'), life: 3000 });
        await loadData();
    } catch (error) {
        toast.add({ severity: 'error', summary: t('common.error'), detail: t('news.delete_failed'), life: 3000 });
    }
}
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <div class="font-semibold text-xl">{{ $t('news.title') }}</div>
                <Button :label="$t('news.add_news')" icon="pi pi-plus" @click="openNew" />
            </div>
        </div>

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="title" :header="$t('news.title_field')">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.title }}</span>
                    </template>
                </Column>
                <Column field="author" :header="$t('news.author')" style="width: 150px" />
                <Column field="date" :header="$t('news.date')" style="width: 180px" />
                <Column :header="$t('common.actions')" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" text rounded size="small" @click="editNews(data)" />
                            <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('news.no_news') }}</div>
                </template>
            </DataTable>
        </div>

        <Dialog v-model:visible="showDialog" :header="form.id ? $t('news.edit_title') : $t('news.add_title')" modal class="w-full max-w-2xl">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="title" class="font-semibold">{{ $t('news.title_field') }}</label>
                    <InputText id="title" v-model="form.title" class="w-full" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="message" class="font-semibold">{{ $t('news.external_msg') }}</label>
                    <Textarea id="message" v-model="form.message" rows="4" class="w-full" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="message2" class="font-semibold">{{ $t('news.internal_msg') }}</label>
                    <Textarea id="message2" v-model="form.message2" rows="4" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button :label="$t('common.cancel')" text @click="showDialog = false" />
                <Button :label="form.id ? $t('common.save') : $t('common.create')" :loading="saving" @click="saveNews" />
            </template>
        </Dialog>

        <ConfirmDialog />
    </Fluid>
</template>
