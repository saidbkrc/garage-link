<template>
    <AppLayout title="Cihazlar" currentPage="Cihazlar" :user="user">

        <!-- Page Heading -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black tracking-tight">Cihazlar</h2>
                <p class="text-slate-500 dark:text-slate-400">Aktif cihazlarınızı yönetin.</p>
            </div>
            <button @click="openPendingModal"
                class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary/90 transition-all shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined">add</span>
                Cihaz Ekle
            </button>
        </div>

        <!-- Filters & Toolbar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 mb-6 flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input v-model="filters.search"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm focus:ring-2 focus:ring-primary/50"
                        placeholder="Cihaz ara..." type="text" />
                </div>
            </div>

            <select v-model="filters.room"
                class="bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm py-2.5 pl-4 pr-10 focus:ring-2 focus:ring-primary/50">
                <option value="">Tüm Odalar</option>
                <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
            </select>

            <select v-model="filters.category"
                class="bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm py-2.5 pl-4 pr-10 focus:ring-2 focus:ring-primary/50">
                <option value="">Tüm Kategoriler</option>
                <option value="lighting">Aydınlatma</option>
                <option value="climate">İklimlendirme</option>
                <option value="security">Güvenlik</option>
                <option value="plug">Enerji</option>
                <option value="sensor">Sensörler</option>
                <option value="curtain">Perde & Motor</option>
            </select>

            <div class="h-8 w-px bg-slate-200 dark:bg-slate-800 mx-1"></div>

            <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-lg">
                <button @click="filters.status = ''"
                    class="px-4 py-1.5 text-sm font-semibold rounded-md transition-all"
                    :class="filters.status === '' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-slate-500 hover:text-primary'">
                    Tümü
                </button>
                <button @click="filters.status = 'online'"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all"
                    :class="filters.status === 'online' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-slate-500 hover:text-primary'">
                    Çevrimiçi
                </button>
                <button @click="filters.status = 'offline'"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all"
                    :class="filters.status === 'offline' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-slate-500 hover:text-primary'">
                    Çevrimdışı
                </button>
            </div>

            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg ml-auto">
                <button @click="viewMode = 'grid'" class="p-1.5 rounded-md transition-all"
                    :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500'">
                    <span class="material-symbols-outlined text-[20px]">grid_view</span>
                </button>
                <button @click="viewMode = 'table'" class="p-1.5 rounded-md transition-all"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500'">
                    <span class="material-symbols-outlined text-[20px]">list</span>
                </button>
            </div>
        </div>

        <!-- Boş durum -->
        <div v-if="filteredDevices.length === 0 && devices.length === 0"
            class="p-12 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-400 block mb-3">devices_off</span>
            <p class="font-bold text-slate-600 dark:text-slate-400">Henüz aktif cihaz yok</p>
            <p class="text-sm text-slate-500 mt-1">Gateway'den cihaz tarayıp aktifleştirin.</p>
            <button @click="openPendingModal" class="mt-4 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">
                Cihaz Ekle
            </button>
        </div>

        <!-- Table View -->
        <div v-else-if="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-4 w-12 text-center">
                                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                    class="rounded border-slate-300 text-primary focus:ring-primary" />
                            </th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Cihaz</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Kategori</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Durum</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Anlık Veri</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Son Görülme</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center" title="Dashboard'da göster">
                                <span class="material-symbols-outlined text-base text-slate-400">push_pin</span>
                            </th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr v-for="device in filteredDevices" :key="device.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 text-center">
                                <input type="checkbox" v-model="selectedDevices" :value="device.id"
                                    class="rounded border-slate-300 text-primary focus:ring-primary" />
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-lg flex items-center justify-center"
                                        :class="getCategoryColor(device.category)">
                                        <span class="material-symbols-outlined">{{ device.icon || 'memory' }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold">{{ device.name }}</p>
                                        <p class="text-xs text-slate-500">{{ device.room || 'Oda atanmamış' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getCategoryBadge(device.category)">
                                    {{ getCategoryLabel(device.category) }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <span class="size-2 rounded-full"
                                        :class="device.is_online ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                    <span class="text-sm font-medium">{{ device.is_online ? 'Çevrimiçi' : 'Çevrimdışı' }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                <p class="text-sm" :class="device.is_online ? 'font-medium' : 'text-slate-400 italic'">
                                    {{ getDeviceStateText(device) }}
                                </p>
                            </td>
                            <td class="p-4 text-sm text-slate-500">{{ getLastSeen(device) }}</td>
                            <!-- Dashboard pin toggle -->
                            <td class="p-4 text-center">
                                <button @click="toggleDashboard(device)"
                                    class="size-8 rounded-lg flex items-center justify-center mx-auto transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                    :title="device.show_in_dashboard ? 'Dashboard\'dan kaldır' : 'Dashboard\'a ekle'">
                                    <span class="material-symbols-outlined text-[20px] transition-colors"
                                        :class="device.show_in_dashboard ? 'text-primary filled-icon' : 'text-slate-300 hover:text-slate-500'">
                                        push_pin
                                    </span>
                                </button>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEdit(device)"
                                        class="p-2 text-slate-400 hover:text-primary transition-colors"
                                        title="Düzenle">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <button @click="confirmDelete(device)"
                                        class="p-2 text-slate-400 hover:text-red-500 transition-colors"
                                        title="Sil">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-800">
                <p class="text-xs font-medium text-slate-500">Toplam <b>{{ filteredDevices.length }}</b> cihaz</p>
            </div>
        </div>

        <!-- Grid View -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div v-for="device in filteredDevices" :key="device.id"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 hover:shadow-lg transition-all">
                <div class="flex items-start justify-between mb-4">
                    <div class="size-12 rounded-xl flex items-center justify-center"
                        :class="getCategoryColor(device.category)">
                        <span class="material-symbols-outlined text-2xl">{{ device.icon || 'memory' }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <!-- Pin button -->
                        <button @click.stop="toggleDashboard(device)"
                            class="size-8 rounded-lg flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            :title="device.show_in_dashboard ? 'Dashboard\'dan kaldır' : 'Dashboard\'a ekle'">
                            <span class="material-symbols-outlined text-[18px]"
                                :class="device.show_in_dashboard ? 'text-primary filled-icon' : 'text-slate-300'">
                                push_pin
                            </span>
                        </button>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2 rounded-full" :class="device.is_online ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                        </div>
                    </div>
                </div>
                <h3 class="font-bold text-sm mb-1">{{ device.name }}</h3>
                <p class="text-xs text-slate-500 mb-3">{{ device.room || 'Oda atanmamış' }}</p>
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                        :class="getCategoryBadge(device.category)">
                        {{ getCategoryLabel(device.category) }}
                    </span>
                    <div class="flex gap-1">
                        <button @click.stop="openEdit(device)"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-primary hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined text-base">edit</span>
                        </button>
                        <button @click.stop="confirmDelete(device)"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">sensors</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Çevrimiçi</p>
                    <p class="text-2xl font-black">{{ stats.online }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Çevrimdışı</p>
                    <p class="text-2xl font-black">{{ stats.offline }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-primary/10 text-primary rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">push_pin</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Dashboard'da</p>
                    <p class="text-2xl font-black">{{ stats.pinned }}</p>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <Transition name="slide-up">
            <div v-if="selectedDevices.length > 0"
                class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-4">
                <span class="text-sm font-medium">{{ selectedDevices.length }} cihaz seçildi</span>
                <div class="h-6 w-px bg-slate-700 dark:bg-slate-300"></div>
                <button @click="bulkDelete" class="px-4 py-1.5 bg-red-500 text-white rounded-lg text-sm font-bold hover:bg-red-600">
                    Toplu Sil
                </button>
                <button @click="selectedDevices = []" class="p-1.5 hover:bg-slate-800 dark:hover:bg-slate-100 rounded-lg">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </Transition>

        <!-- ===== EDIT MODAL ===== -->
        <div v-if="editTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-lg text-slate-900 dark:text-white">Cihazı Düzenle</h3>
                    <button @click="editTarget = null" class="size-9 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 flex items-center justify-center">
                        <span class="material-symbols-outlined text-slate-500">close</span>
                    </button>
                </div>
                <form @submit.prevent="saveEdit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Cihaz Adı</label>
                        <input v-model="editForm.name" type="text" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Oda</label>
                        <select v-model="editForm.room_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option :value="null">Oda Atanmamış</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Cihaz Tipi</label>
                        <select v-model="editForm.device_type_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option :value="null">— Seçiniz —</option>
                            <option v-for="dt in deviceTypes" :key="dt.id" :value="dt.id">{{ dt.name }}</option>
                        </select>
                    </div>
                    <!-- Dashboard toggle -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <div>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Dashboard Hızlı Kontrol</p>
                            <p class="text-xs text-slate-500">Ana sayfada göster</p>
                        </div>
                        <button type="button" @click="editForm.show_in_dashboard = !editForm.show_in_dashboard"
                            class="relative w-11 h-6 rounded-full transition-colors"
                            :class="editForm.show_in_dashboard ? 'bg-primary' : 'bg-slate-300 dark:bg-slate-600'">
                            <span class="absolute top-0.5 size-5 bg-white rounded-full transition-all shadow"
                                :class="editForm.show_in_dashboard ? 'left-5' : 'left-0.5'"></span>
                        </button>
                    </div>
                    <!-- Röle kanal sayısı (sadece relay tipinde göster) -->
                    <div v-if="isRelay">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Kanal Sayısı</label>
                        <select v-model.number="editForm.channel_count"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option :value="1">1 Kanal</option>
                            <option :value="2">2 Kanal</option>
                            <option :value="3">3 Kanal</option>
                            <option :value="4">4 Kanal</option>
                            <option :value="6">6 Kanal</option>
                            <option :value="8">8 Kanal</option>
                        </select>
                    </div>
                    <div v-if="editErrors.length" class="px-3 py-2 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200">
                        <p v-for="e in editErrors" :key="e" class="text-xs text-red-600">{{ e }}</p>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="editTarget = null"
                            class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium">
                            İptal
                        </button>
                        <button type="submit" :disabled="savingEdit"
                            class="flex-1 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm disabled:opacity-50">
                            {{ savingEdit ? 'Kaydediliyor...' : 'Kaydet' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== DELETE CONFIRM MODAL ===== -->
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-sm p-6 text-center">
                <span class="material-symbols-outlined text-4xl text-red-500 block mb-3">delete_forever</span>
                <h3 class="font-bold text-lg mb-1">Cihazı Sil</h3>
                <p class="text-sm text-slate-500 mb-6">
                    <strong>{{ deleteTarget.name }}</strong> silinecek. Bu işlem geri alınamaz.
                </p>
                <div class="flex gap-3">
                    <button @click="deleteTarget = null"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium">
                        İptal
                    </button>
                    <button @click="doDelete" :disabled="deleting"
                        class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-bold disabled:opacity-50">
                        {{ deleting ? 'Siliniyor...' : 'Evet, Sil' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== PENDING DEVICES MODAL (Cihaz Ekle) ===== -->
        <div v-if="showPendingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-lg text-slate-900 dark:text-white">Gateway'den Cihaz Ekle</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Bulunan ama henüz aktifleştirilmemiş cihazlar</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="loadPendingDevices" :disabled="loadingPending"
                            class="size-9 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 flex items-center justify-center">
                            <span class="material-symbols-outlined text-base" :class="loadingPending ? 'animate-spin' : ''">refresh</span>
                        </button>
                        <button @click="showPendingModal = false"
                            class="size-9 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 flex items-center justify-center">
                            <span class="material-symbols-outlined text-slate-500">close</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-y-auto flex-1 p-6">
                    <div v-if="loadingPending" class="flex items-center justify-center py-12">
                        <span class="material-symbols-outlined text-4xl text-slate-400 animate-spin">refresh</span>
                    </div>
                    <div v-else-if="pendingDevices.length === 0" class="text-center py-12">
                        <span class="material-symbols-outlined text-5xl text-slate-400 block mb-3">devices_off</span>
                        <p class="font-medium text-slate-500">Bekleyen cihaz yok</p>
                        <p class="text-xs text-slate-400 mt-1">
                            Gateway sayfasından "Cihaz Tara" yaparak yeni cihazları keşfedin.
                        </p>
                        <a href="/gateways" class="mt-4 inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">
                            <span class="material-symbols-outlined text-base">router</span>
                            Gateway'e Git
                        </a>
                    </div>
                    <div v-else class="space-y-3">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                            {{ pendingDevices.length }} cihaz bekliyor
                        </p>
                        <div v-for="device in pendingDevices" :key="device.id"
                            class="p-4 rounded-2xl border transition-all"
                            :class="pendingForms[device.id]?.saved
                                ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/10'
                                : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50'">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="size-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                    :class="pendingForms[device.id]?.saved ? 'bg-green-100 text-green-600' : 'bg-primary/10 text-primary'">
                                    <span class="material-symbols-outlined text-lg">memory</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-slate-400 font-mono truncate">{{ device.ieee_addr }}</p>
                                    <p class="text-xs text-slate-500">{{ device.gateway_name || device.gateway }}</p>
                                </div>
                                <span v-if="pendingForms[device.id]?.saved"
                                    class="text-xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                                    ✓ Eklendi
                                </span>
                            </div>
                            <input v-if="pendingForms[device.id]"
                                v-model="pendingForms[device.id].name"
                                :disabled="pendingForms[device.id].saved"
                                placeholder="Cihaz adı (örn: Oturma Odası LED)"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm mb-2 outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-60" />
                            <select v-if="pendingForms[device.id]"
                                v-model="pendingForms[device.id].device_type_id"
                                :disabled="pendingForms[device.id].saved"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm mb-3 outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-60">
                                <option :value="null">— Cihaz tipi seç —</option>
                                <option v-for="dt in deviceTypes" :key="dt.id" :value="dt.id">{{ dt.name }}</option>
                            </select>
                            <button v-if="pendingForms[device.id] && !pendingForms[device.id].saved"
                                @click="activatePending(device.id)"
                                :disabled="pendingForms[device.id].saving || !pendingForms[device.id].name"
                                class="w-full py-2 rounded-xl bg-primary hover:bg-primary/90 text-white text-sm font-semibold disabled:opacity-50 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-base"
                                    :class="pendingForms[device.id].saving ? 'animate-spin' : ''">
                                    {{ pendingForms[device.id].saving ? 'refresh' : 'check_circle' }}
                                </span>
                                {{ pendingForms[device.id].saving ? 'Ekleniyor...' : 'Aktifleştir' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="pendingDevices.length > 0" class="p-5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-400">
                        {{ Object.values(pendingForms).filter(f => f.saved).length }} / {{ pendingDevices.length }} eklendi
                    </p>
                    <button @click="showPendingModal = false; router.reload({ only: ['devices'] })"
                        class="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold">
                        Tamam
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <Transition name="slide-up">
            <div v-if="toast"
                class="fixed bottom-5 right-5 z-50 bg-white dark:bg-slate-800 shadow-xl rounded-xl p-4 flex items-center gap-3 border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined" :class="toast.type === 'success' ? 'text-emerald-500' : 'text-red-500'">
                    {{ toast.type === 'success' ? 'check_circle' : 'error' }}
                </span>
                <span class="text-sm font-medium">{{ toast.message }}</span>
            </div>
        </Transition>

    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    devices:     { type: Array, default: () => [] },
    rooms:       { type: Array, default: () => [] },
    deviceTypes: { type: Array, default: () => [] },
    initialRoom: { type: String, default: '' },
    user:        { type: Object, default: null },
});

// ─── Canlı güncelleme (Reverb / WebSocket) ──────────────────────────────────
// Cihaz durumu MQTT'den geldiğinde backend DeviceStateUpdated event'i yayınlar;
// burada dinleyip sayfayı yenilemeden cihaz kartlarını güncelleriz.
const dealerId = usePage().props.auth?.user?.dealer_id;
let stateChannel = null;

function applyLiveUpdate(payload) {
    const device = props.devices.find(d => d.id === payload.device_id);
    if (!device) return;
    if (payload.is_online !== undefined) device.is_online = payload.is_online;
    if (payload.state) device.state = { ...device.state, ...payload.state };
}

onMounted(() => {
    if (!dealerId || !window.Echo) return;
    stateChannel = window.Echo.private(`dealer.${dealerId}`)
        .listen('.device.state', applyLiveUpdate);
});

onUnmounted(() => {
    if (dealerId && window.Echo) {
        window.Echo.leave(`dealer.${dealerId}`);
    }
});

// ─── State ─────────────────────────────────────────────────────────────────
const viewMode       = ref('table');
const selectAll      = ref(false);
const selectedDevices = ref([]);
const toast          = ref(null);

const filters = reactive({
    search:   '',
    room:     props.initialRoom || '',
    category: '',
    status:   '',
});

// Edit
const editTarget  = ref(null);
const editForm    = ref({});
const editErrors  = ref([]);
const savingEdit  = ref(false);

// Delete
const deleteTarget = ref(null);
const deleting     = ref(false);

// Pending devices
const showPendingModal = ref(false);
const pendingDevices   = ref([]);
const pendingForms     = ref({});
const loadingPending   = ref(false);

// ─── Computed ──────────────────────────────────────────────────────────────
const stats = computed(() => ({
    total:  props.devices.length,
    online: props.devices.filter(d => d.is_online).length,
    offline: props.devices.filter(d => !d.is_online).length,
    pinned: props.devices.filter(d => d.show_in_dashboard).length,
}));

const filteredDevices = computed(() => {
    return props.devices.filter(device => {
        if (filters.search && !device.name.toLowerCase().includes(filters.search.toLowerCase())) return false;
        if (filters.room && device.room_id !== parseInt(filters.room)) return false;
        if (filters.category && device.category !== filters.category) return false;
        if (filters.status === 'online' && !device.is_online) return false;
        if (filters.status === 'offline' && device.is_online) return false;
        return true;
    });
});

// ─── Helpers ───────────────────────────────────────────────────────────────
const showToast = (message, type = 'success') => {
    toast.value = { message, type };
    setTimeout(() => toast.value = null, 3000);
};

const getHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
});

const toggleSelectAll = () => {
    selectedDevices.value = selectAll.value ? filteredDevices.value.map(d => d.id) : [];
};

// ─── Dashboard toggle ──────────────────────────────────────────────────────
const toggleDashboard = (device) => {
    router.put(`/devices/${device.id}`, { show_in_dashboard: !device.show_in_dashboard }, {
        preserveScroll: true,
        onSuccess: () => showToast(device.show_in_dashboard ? 'Dashboard\'dan kaldırıldı' : 'Dashboard\'a eklendi'),
    });
};

// ─── Edit ──────────────────────────────────────────────────────────────────
const openEdit = (device) => {
    editTarget.value = device;
    editErrors.value = [];
    editForm.value = {
        name:               device.name,
        room_id:            device.room_id ?? null,
        device_type_id:     device.device_type_id ?? null,
        show_in_dashboard:  device.show_in_dashboard ?? false,
        channel_count:      device.config?.channel_count ?? null,
    };
};

const isRelay = computed(() => {
    if (!editTarget.value) return false;
    const selected = props.deviceTypes.find(dt => dt.id === editForm.value.device_type_id);
    return selected?.slug === 'relay' || editTarget.value.type === 'relay';
});

const saveEdit = () => {
    savingEdit.value = true;
    editErrors.value = [];
    router.put(`/devices/${editTarget.value.id}`, editForm.value, {
        onSuccess: () => {
            editTarget.value = null;
            showToast('Cihaz güncellendi');
        },
        onError: (e) => { editErrors.value = Object.values(e).flat(); },
        onFinish: () => savingEdit.value = false,
    });
};

// ─── Delete ────────────────────────────────────────────────────────────────
const confirmDelete = (device) => deleteTarget.value = device;

const doDelete = () => {
    deleting.value = true;
    router.delete(`/devices/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleteTarget.value = null;
            showToast('Cihaz silindi');
        },
        onFinish: () => deleting.value = false,
    });
};

const bulkDelete = () => {
    if (!confirm(`${selectedDevices.value.length} cihaz silinecek. Emin misiniz?`)) return;
    // Sırayla sil
    const ids = [...selectedDevices.value];
    let count = 0;
    const deleteNext = (i) => {
        if (i >= ids.length) {
            selectedDevices.value = [];
            showToast(`${count} cihaz silindi`);
            return;
        }
        router.delete(`/devices/${ids[i]}`, {
            preserveState: true,
            onSuccess: () => { count++; deleteNext(i + 1); },
        });
    };
    deleteNext(0);
};

// ─── Pending devices modal ─────────────────────────────────────────────────
const openPendingModal = async () => {
    showPendingModal.value = true;
    await loadPendingDevices();
};

const loadPendingDevices = async () => {
    loadingPending.value = true;
    pendingDevices.value = [];
    pendingForms.value = {};
    try {
        const res = await fetch('/api/v1/devices/pending', { headers: getHeaders() });
        const data = await res.json();
        if (data.success) {
            pendingDevices.value = data.data;
            data.data.forEach(d => {
                pendingForms.value[d.id] = {
                    name:           d.name || '',
                    device_type_id: d.type_id ?? null,
                    saving:         false,
                    saved:          false,
                };
            });
        }
    } catch (e) {
        console.error('Pending devices error:', e);
    } finally {
        loadingPending.value = false;
    }
};

const activatePending = async (deviceId) => {
    const form = pendingForms.value[deviceId];
    if (!form || form.saving || form.saved) return;
    form.saving = true;
    try {
        const res = await fetch(`/api/v1/devices/${deviceId}`, {
            method: 'PUT',
            headers: getHeaders(),
            body: JSON.stringify({
                name:           form.name,
                device_type_id: form.device_type_id,
                is_active:      true,
            }),
        });
        if (res.ok) {
            form.saved = true;
            showToast(`${form.name} eklendi`);
        }
    } catch (e) {
        console.error('Activate error:', e);
    } finally {
        form.saving = false;
    }
};

// ─── Category helpers ──────────────────────────────────────────────────────
const getCategoryColor = (cat) => ({
    lighting: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
    climate:  'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',
    security: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
    plug:     'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
    sensor:   'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600',
    curtain:  'bg-amber-100 dark:bg-amber-900/30 text-amber-600',
}[cat] || 'bg-slate-100 dark:bg-slate-800 text-slate-600');

const getCategoryBadge = (cat) => ({
    lighting: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    climate:  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
    security: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    plug:     'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
    sensor:   'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300',
    curtain:  'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
}[cat] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300');

const getCategoryLabel = (cat) => ({
    lighting: 'Aydınlatma', climate: 'İklimlendirme', security: 'Güvenlik',
    plug: 'Enerji', sensor: 'Sensör', curtain: 'Perde',
}[cat] || cat || 'Diğer');

const getDeviceStateText = (device) => {
    if (!device.is_online) return 'Sinyal Yok';
    const s = device.state || {};
    if (device.category === 'lighting') return s.power ? `Açık %${s.brightness || 100}` : 'Kapalı';
    if (device.category === 'climate') return `${s.current_temp || s.target_temp || 22}°C`;
    if (device.category === 'plug') return s.power ? `Açık ${s.power_watt || 0}W` : 'Kapalı';
    if (device.category === 'curtain') return `%${s.position || 0} Açık`;
    if (device.category === 'sensor') {
        if (s.temperature) return `${s.temperature}°C`;
        if (s.humidity) return `%${s.humidity}`;
    }
    return 'Aktif';
};

const getLastSeen = (device) => {
    if (device.is_online) return 'Şimdi';
    if (!device.last_seen_at) return 'Bilinmiyor';
    const d = new Date(device.last_seen_at);
    const diff = Math.floor((new Date() - d) / 1000);
    if (diff < 3600) return `${Math.floor(diff / 60)} dk önce`;
    return `${Math.floor(diff / 3600)} sa önce`;
};
</script>

<style>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translate(-50%, 20px); }
</style>
