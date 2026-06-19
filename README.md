# GarageLink

Bayi (dealer) odaklı **IoT akıllı ev / cihaz yönetim paneli**. Bayiler, sahaya kurdukları
Zigbee gateway'leri ve cihazları (röle, anahtar, ampul, LED şerit, sensör, termostat) tek bir
panelden yönetir; cihazlara komut gönderir, durumlarını izler, oda/senaryo/zamanlama tanımlar.

Cihazlarla iletişim **MQTT** üzerinden, bir Zigbee gateway aracılığıyla yapılır.

---

## Teknoloji Yığını

| Katman        | Teknoloji                                              |
|---------------|--------------------------------------------------------|
| Backend       | Laravel 12 (PHP 8.2)                                    |
| Frontend      | Vue 3 + Inertia.js + Tailwind CSS 4 (Vite)             |
| Admin paneli  | Laravel Nova 5                                          |
| Auth          | Çift guard — `dealer` (web session) + Sanctum (API)    |
| Mesajlaşma    | MQTT (`php-mqtt/laravel-client`), EMQX broker          |
| Cache / State | Redis                                                   |
| Veritabanı    | MySQL                                                   |

---

## Mimari

```
  ┌─────────────┐         MQTT          ┌──────────────┐        Zigbee
  │   Cihazlar  │◄────────────────────► │   Gateway    │◄───────────────► Röle/Ampul/Sensör
  │  (Zigbee)   │                       │  (pigasoft)  │
  └─────────────┘                       └──────┬───────┘
                                               │ MQTT (broker)
                          ┌────────────────────┴────────────────────┐
                          │                                         │
                  ┌───────▼────────┐                       ┌────────▼─────────┐
                  │ mqtt:subscribe │  (sürekli dinleyici)  │   MqttService    │ (panel → cihaz publish)
                  │  Artisan cmd   │                       │                  │
                  └───────┬────────┘                       └────────┬─────────┘
                          │  yazar                                   │ yazar
                  ┌───────▼──────────────────────────────────────────▼───────┐
                  │              MySQL  +  Redis (device state cache)         │
                  └───────────────────────────┬──────────────────────────────┘
                                               │
                                  ┌────────────▼────────────┐
                                  │  Laravel (Web + API/V1) │
                                  └────────────┬────────────┘
                                               │ Inertia
                                  ┌────────────▼────────────┐
                                  │   Vue 3 Bayi Paneli     │
                                  └─────────────────────────┘
```

**İki yönlü akış:**
- **Panel → Cihaz:** `MqttService` her komut için kısa ömürlü bir publish bağlantısı açar
  (`glink_pub_*` client id) → `pigasoft/{gateway_id}/commands` topic'ine yayınlar. Komut
  `DeviceLog` olarak loglanır, beklenen state hem Redis'e hem DB'ye yazılır (iyimser güncelleme).
- **Cihaz → Panel:** `php artisan mqtt:subscribe` sürekli çalışan bir dinleyicidir; gateway/cihaz
  mesajlarını yakalar, DB ve Redis state'ini günceller. Bağlantı koparsa 5 sn'de bir otomatik
  yeniden bağlanır.

---

## MQTT Topic Şeması

Tüm topic'ler `pigasoft/{gateway_id}/{suffix}` desenindedir (örn. `pigasoft/gw_04D9C2FEFFEEF648/data`).

### Gateway/Cihaz → Panel (subscribe edilen — `MqttSubscribe`)

| Topic suffix      | Anlamı                          | Örnek payload |
|-------------------|---------------------------------|---------------|
| `gateway`         | Gateway kendini tanıtır (hello) | `{"type":"gateway_hello","gateway_id":"gw_XXX","project":"...","fw":"1.0.0","ip":"..."}` |
| `connectionpub`   | Cihaz online/offline olayı      | `{"event":"device_online","ieee_addr":"CCD8..."}` |
| `data`            | Cihaz durum güncellemesi        | `{"ieee_addr":"CCD8...","power":true,"brightness":80,"color":"rgb(255,0,0)"}` |
| `devicelist`      | Cihaz keşfi (her cihaz ayrı msj)| `{"index":2,"ieee_addr":"58DE...","endpoints":[{"in_clusters":[0,3,4,5,6]}]}` |
| `health`          | Gateway sağlık/uptime           | `{"uptime_seconds":12345}` |
| `scan_mode`       | Tarama sonuçları                | — |
| `commands`        | Panelin yayınladığı komutlar (loglama/echo) | — |

### Panel → Gateway (publish edilen — `MqttService`)

Komut topic'leri DB'de `commands.mqtt_topic` alanında saklanır ve `{gateway_id}` yer tutucusu
çalışma anında ilgili gateway ID'siyle değiştirilir. Payload `Command::buildPayload()` ile üretilir
ve cihaz tanımlayıcısı olarak öncelikle `ieee_addr` (Zigbee donanım adresi), yoksa `device_index`
eklenir.

### Cihaz tipi tespiti (Zigbee cluster → tip)

`devicelist` mesajındaki endpoint cluster'larından cihaz tipi otomatik çıkarılır:

| Cluster(lar)            | Tip (`slug`)           |
|-------------------------|------------------------|
| 768 (Color) + 8 (Level) | `led_strip` (RGB LED)  |
| 8 (Level), 768 yok      | `bulb` (dim. ampul)    |
| 6 (On/Off), 8 yok       | `switch` (röle/anahtar)|
| 513 (Thermostat)        | `climate_ac`           |
| 1026 (Temperature)      | `sensor_temperature`   |
| 1030 (Occupancy)        | `sensor_motion`        |
| `device_name` `relay_*` | `relay` (röle kartı, çok kanallı) |

---

## Veri Modeli (ana tablolar)

- **Dealer** → **DealerUser** — bayi ve panel kullanıcıları (`dealer` auth guard)
- **Gateway** — sahadaki ağ geçidi. MQTT'de keşfedilince `dealer_id = NULL` (sahipsiz) oluşturulur;
  bayi panelden **claim** ederek sahiplenir.
- **Device** + **DeviceType** — cihazlar ve tipleri. Yeni keşfedilen cihaz `is_active=false` başlar;
  bayi isim verip aktifleştirir. Çok kanallı röle kartı `config.channel_count` ile tutulur.
- **Room** — oda bazlı gruplama
- **Scene** — senaryolar (tek tıkla çoklu komut)
- **Schedule** — zamanlanmış komutlar
- **Command** — komut tanımları (`slug`, `mqtt_topic`, payload şablonu)
- **DeviceLog** — gönderilen her komutun kaydı
- **Alert**, **EnergyUsage** — uyarılar ve enerji tüketimi

---

## Kurulum

### Gereksinimler
- PHP 8.2+, Composer
- Node.js 18+ / npm
- MySQL
- Docker (Redis + EMQX broker için) — veya harici Redis/MQTT broker

### Adımlar

```bash
# 1. Bağımlılıklar
composer install
npm install

# 2. Ortam dosyası
cp .env.example .env
php artisan key:generate
#   .env içinde DB_*, REDIS_*, MQTT_* değerlerini doldurun

# 3. Altyapı (Redis + EMQX) — Docker ile
docker-compose up -d
#   EMQX dashboard: http://localhost:18083

# 4. Veritabanı
php artisan migrate --seed   # seeder'lar demo bayi/cihaz/oda/senaryo verisi basar

# 5. Frontend
npm run dev                  # geliştirme (prod için: npm run build)
```

---

## Çalıştırma

Üç süreç birlikte çalışır:

```bash
# 1) Web sunucusu
php artisan serve              # http://localhost:8000

# 2) Vite dev server (frontend)
npm run dev

# 3) MQTT dinleyici (cihaz mesajlarını işler) — SÜREKLİ çalışmalı
php artisan mqtt:subscribe

# 4) Reverb WebSocket sunucusu (canlı güncelleme) — SÜREKLİ çalışmalı
php artisan reverb:start
```

> Windows'ta MQTT dinleyiciyi başlatmak için `start-mqtt.bat` kullanılabilir.
> Üretimde dinleyici `supervisor.conf` ile süreç yöneticisi altında çalıştırılır
> (çökerse otomatik yeniden başlar).

### Geliştirme yardımcısı
`GET /test/mqtt/{command}` — bir cihaza hızlıca komut gönderir.
**Yalnızca `local` ortamda ve giriş yapmış bayi için** kayıtlıdır; üretimde hiç yüklenmez.

---

## Canlı Güncelleme (Reverb / WebSocket)

Cihaz durumları panele **gerçek zamanlı** yansır (sayfa yenilemeye gerek yok):

1. Cihaz/gateway MQTT mesajı yollar → `mqtt:subscribe` işler ve DB/Redis'i günceller.
2. Dinleyici `DeviceStateUpdated` event'ini yayınlar (`ShouldBroadcastNow` — anında, queue worker
   gerektirmez).
3. Event, bayiye özel **private kanala** gider: `private-dealer.{dealer_id}`
   (yetki: `routes/channels.php`, `dealer` guard).
4. Vue tarafında `resources/js/echo.js` (Laravel Echo + Reverb) kanalı dinler; `Devices/Index.vue`
   gelen `.device.state` olayıyla ilgili cihazın `is_online`/`state` alanlarını günceller.

Gerekli `.env` anahtarları: `REVERB_APP_ID/KEY/SECRET`, `REVERB_HOST/PORT/SCHEME` ve frontend için
`VITE_REVERB_*`. Reverb sunucusu `php artisan reverb:start` ile çalışır (üretimde supervisor altında).

## API (V1)

Tüm uçlar `/api/v1` altında. Auth: `auth:dealer,sanctum`. Login `throttle:6,1` ile korunur.

| Method | Uç                                  | Açıklama |
|--------|-------------------------------------|----------|
| POST   | `/auth/login`                       | Giriş (token) |
| GET    | `/auth/me`, POST `/auth/logout`     | Oturum |
| GET    | `/devices`, `/devices/{id}`         | Cihaz listesi / detay |
| GET    | `/devices/states`                   | Tüm cihaz state'lerini toplu döner |
| POST   | `/devices/sync`                     | Tüm cihazlara `get_state` yollar |
| POST   | `/devices/{id}/command`             | Cihaza komut gönder |
| POST   | `/commands/group`                   | Grup komutu |
| GET    | `/gateways`                         | Gateway listesi |
| POST   | `/gateways/{id}/claim`              | Gateway sahiplen |
| POST   | `/gateways/{id}/scan`, `/scan-all`  | Cihaz tara |
| POST   | `/scenes/{scene}/run`               | Senaryo çalıştır |

---

## Önemli `.env` Anahtarları

```ini
DB_CONNECTION=mysql           # DB_HOST/DATABASE/USERNAME/PASSWORD
REDIS_HOST=127.0.0.1          # cihaz state cache
MQTT_HOST=...                 # MQTT broker (örn. EMQX / hivemq)
MQTT_PORT=1883
MQTT_CLIENT_ID=garagelink_sub_001   # dinleyici client id
```

> **Üretim notu:** Canlıda `APP_DEBUG=false` ve `APP_ENV=production` olmalıdır.
> Uzak MySQL erişim listesinde (`Remote Database Access`) `%` joker host'ları kullanmaktan kaçının.
