<template>
    <AppLayout title="MQTT İzleyici" currentPage="MQTT">
        <div class="p-4 sm:p-6 max-w-6xl mx-auto">
            <!-- Başlık -->
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">monitor_heart</span>
                        Canlı MQTT İzleyici
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Tarayıcı doğrudan broker'a bağlanır — backend gerekmez
                    </p>
                </div>

                <!-- Bağlantı durumu -->
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-bold"
                    :class="statusClass">
                    <span class="size-2.5 rounded-full" :class="dotClass"
                        :style="status === 'connected' ? 'box-shadow:0 0 8px currentColor' : ''"></span>
                    {{ statusLabel }}
                </div>
            </div>

            <!-- Kontrol çubuğu -->
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                <div class="flex items-center gap-2 flex-1 min-w-[220px]">
                    <span class="material-symbols-outlined text-slate-400 text-xl">filter_alt</span>
                    <input v-model="topic" @keyup.enter="resubscribe"
                        class="flex-1 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-mono focus:border-primary outline-none"
                        placeholder="pigasoft/#" />
                    <button @click="resubscribe"
                        class="px-3 py-2 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-primary/90 whitespace-nowrap">
                        Abone Ol
                    </button>
                </div>

                <input v-model="filterText"
                    class="px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm w-40"
                    placeholder="Satır filtrele…" />

                <button @click="paused = !paused"
                    class="px-3 py-2 rounded-lg text-sm font-semibold border transition-colors"
                    :class="paused
                        ? 'bg-amber-500 text-white border-amber-500'
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'">
                    {{ paused ? '▶ Devam' : '⏸ Duraklat' }}
                </button>

                <button @click="clear"
                    class="px-3 py-2 rounded-lg text-sm font-semibold bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-red-400 hover:text-red-500">
                    Temizle
                </button>
            </div>

            <!-- Terminal -->
            <div class="rounded-2xl overflow-hidden border border-slate-800 shadow-2xl bg-[#0b0f17]">
                <!-- Terminal başlık çubuğu -->
                <div class="flex items-center gap-2 px-4 py-2.5 bg-[#141b27] border-b border-slate-800">
                    <span class="size-3 rounded-full bg-red-500/80"></span>
                    <span class="size-3 rounded-full bg-amber-500/80"></span>
                    <span class="size-3 rounded-full bg-green-500/80"></span>
                    <span class="ml-3 text-xs font-mono text-slate-400 truncate">{{ wsUrl }}</span>
                    <span class="ml-auto text-xs font-mono text-slate-500">{{ filtered.length }} / {{ messages.length }} mesaj</span>
                </div>

                <!-- Mesaj akışı -->
                <div ref="scrollBox"
                    class="h-[60vh] overflow-y-auto px-4 py-3 font-mono text-[13px] leading-relaxed">
                    <div v-if="!filtered.length" class="text-slate-600 italic py-8 text-center">
                        Mesaj bekleniyor… (gateway online ise heartbeat ~10 sn'de bir gelir)
                    </div>

                    <div v-for="msg in filtered" :key="msg.id"
                        class="py-1.5 border-b border-slate-800/40 last:border-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-slate-500 text-[11px]">{{ msg.time }}</span>
                            <span class="px-1.5 py-0.5 rounded text-[11px] font-bold" :class="topicColor(msg.suffix)">
                                {{ msg.topic }}
                            </span>
                        </div>
                        <pre class="mt-1 whitespace-pre-wrap break-all text-slate-200">{{ msg.body }}</pre>
                    </div>
                </div>
            </div>

            <p class="text-xs text-slate-400 mt-3">
                Bağlantı durumu <b>connected</b> ama hiç mesaj gelmiyorsa → gateway broker'a bağlı değildir.
                Bu sayfa salt-okunurdur; komut göndermez.
            </p>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import mqtt from 'mqtt';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    mqttHost:    { type: String, default: 'broker.hivemq.com' },
    topicPrefix: { type: String, default: 'pigasoft/#' },
});

const topic      = ref(props.topicPrefix);
const filterText = ref('');
const paused     = ref(false);
const status     = ref('connecting'); // connecting | connected | reconnecting | error | closed
const messages   = ref([]);
const scrollBox  = ref(null);

let client = null;
let counter = 0;
const MAX = 500;

// WebSocket URL — https sayfada wss, http sayfada ws (HiveMQ standart portları)
const wsUrl = computed(() => {
    const secure = window.location.protocol === 'https:';
    const port = secure ? 8884 : 8000;
    return `${secure ? 'wss' : 'ws'}://${props.mqttHost}:${port}/mqtt`;
});

const filtered = computed(() => {
    if (!filterText.value) return messages.value;
    const q = filterText.value.toLowerCase();
    return messages.value.filter(m => (m.topic + m.body).toLowerCase().includes(q));
});

const statusLabel = computed(() => ({
    connecting: 'Bağlanıyor…',
    connected: 'Bağlı',
    reconnecting: 'Yeniden bağlanıyor…',
    error: 'Hata',
    closed: 'Kapalı',
}[status.value] || status.value));

const statusClass = computed(() => ({
    connected: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    error: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
}[status.value] || 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'));

const dotClass = computed(() => ({
    connected: 'bg-green-500',
    error: 'bg-red-500',
}[status.value] || 'bg-amber-500 animate-pulse'));

function topicColor(suffix) {
    return {
        data:          'bg-blue-500/15 text-blue-300',
        commands:      'bg-amber-500/15 text-amber-300',
        health:        'bg-green-500/15 text-green-300',
        errors:        'bg-red-500/15 text-red-300',
        gateway:       'bg-purple-500/15 text-purple-300',
        devicelist:    'bg-cyan-500/15 text-cyan-300',
        scheduler:     'bg-pink-500/15 text-pink-300',
        connectionpub: 'bg-teal-500/15 text-teal-300',
    }[suffix] || 'bg-slate-600/30 text-slate-300';
}

function addMessage(topicStr, payload) {
    if (paused.value) return;
    let body = payload;
    try {
        body = JSON.stringify(JSON.parse(payload), null, 2);
    } catch (_) { /* JSON değilse ham bırak */ }

    const parts = topicStr.split('/');
    messages.value.push({
        id: ++counter,
        time: new Date().toLocaleTimeString('tr-TR'),
        topic: topicStr,
        suffix: parts[parts.length - 1] || '',
        body,
    });
    if (messages.value.length > MAX) messages.value.splice(0, messages.value.length - MAX);

    nextTick(() => {
        const el = scrollBox.value;
        if (el) el.scrollTop = el.scrollHeight;
    });
}

function resubscribe() {
    if (!client) return;
    client.unsubscribe('#');
    client.subscribe(topic.value, (err) => {
        if (!err) addMessage('[sistem]', `"${topic.value}" topic'ine abone olundu`);
    });
}

function clear() {
    messages.value = [];
}

onMounted(() => {
    client = mqtt.connect(wsUrl.value, {
        clientId: 'glink_web_' + Math.random().toString(16).slice(2, 8),
        clean: true,
        reconnectPeriod: 3000,
        connectTimeout: 10000,
    });

    client.on('connect', () => {
        status.value = 'connected';
        client.subscribe(topic.value);
    });
    client.on('reconnect', () => { status.value = 'reconnecting'; });
    client.on('error', (e) => { status.value = 'error'; console.error('MQTT', e); });
    client.on('close', () => { if (status.value !== 'error') status.value = 'closed'; });
    client.on('message', (t, payload) => addMessage(t, payload.toString()));
});

onUnmounted(() => {
    if (client) { client.end(true); client = null; }
});
</script>
