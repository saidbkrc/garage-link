# CLAUDE.md — Bayi IoT Akıllı Ev Yönetim Paneli

## Proje Özeti
Bayi (dealer) odaklı IoT cihaz yönetim platformu. Bayiler sahaya kurdukları
Zigbee gateway'leri ve bağlı cihazları (röle, anahtar, ampul, RGB LED şerit,
sensör, termostat) tek panelden yönetir: komut gönderir, durum izler,
oda/senaryo/zamanlama tanımlar.

Veri akışı: Cihazlar ⇄ Zigbee Gateway ⇄ MQTT broker (EMQX) ⇄ Laravel ⇄ Vue panel.
İki yönlü çalışır:
- Panel → komut → MqttService → MQTT'ye yayınlanır → gateway → cihaz
- Cihaz → durum değişimi → MQTT → sürekli çalışan `mqtt:subscribe` dinleyicisi
  işler → Reverb (WebSocket) ile panele anlık yansır

Proje ~2-3 ay önce başladı, belirli bir olgunluğa ulaştı. Şu an tamamlama/
sertleştirme aşamasındayız. Güvenlik düzeltmeleri, test altyapısı ve Reverb
canlı güncelleme tamamlandı. Aktif iş: PIYA yeni MQTT komut protokolüne geçiş
— GatewayCommandBuilder + 16 test hazır, kalan entegrasyon firmware tarafından
2 cevap bekliyor (bu kısım blocked, firmware'den dönüş gelmeden ilerletilemez).

## Stack
- Backend: Laravel 12 (PHP 8.2) — web panel + versiyonlu REST API (/api/v1)
- Frontend: Vue 3 + Inertia.js + Tailwind CSS 4 (Vite)
- Admin: Laravel Nova 5
- Auth: Çift guard — `dealer` guard (panel, session bazlı) + Sanctum (API, token bazlı)
  ⚠️ Yeni bir endpoint/sayfa eklerken hangi guard'a ait olduğunu netleştir,
  guard karıştırmak yetkisiz erişime yol açar.
- IoT/Mesajlaşma: MQTT (php-mqtt/laravel-client), broker: EMQX
- Canlı güncelleme: Laravel Reverb (WebSocket) — cihaz durumu sayfa
  yenilemeden anlık yansır
- State/Cache: Redis
- DB: MySQL (uzak sunucu, IP-whitelist'li — local'den bağlanmak için
  whitelist'e IP ekletmek gerekebilir)
- Altyapı: Docker Compose
- Test: PHPUnit, in-memory SQLite izolasyonu kullanılıyor (testler gerçek
  MySQL'e dokunmaz)

## Domain Terimleri / Veri Modeli
- Dealer: panel kullanan bayi (üst seviye hesap)
- DealerUser: bayi altındaki kullanıcı (personel)
- Gateway: Zigbee gateway cihazı; "claim" (sahiplenme) sistemi var — bir
  gateway bir bayiye eşleştirilir
- Device + DeviceType: gateway'e bağlı fiziksel cihaz ve tipi (röle, ampul, sensör vb.)
- Room: cihazların gruplandığı oda/mekan
- Scene: birden fazla cihazı tek komutla tetikleyen senaryo
- Schedule: zamanlamalı otomasyon
- Command: panelden cihaza gönderilen komut kaydı
- DeviceLog: cihaz durum/olay geçmişi
- Alert: uyarı/bildirim
- EnergyUsage: enerji tüketim verisi

## MQTT / Komut Akışı
- Komut üretimi: GatewayCommandBuilder (yeni PIYA protokolüne geçiş sürüyor,
  16 test mevcut)
- Komut gönderimi: MqttService üzerinden yayınlanır
- Dinleme: `mqtt:subscribe` komutu sürekli çalışan bir process olarak
  cihazlardan gelen mesajları dinler ve işler
- ⚠️ MQTT/protokol ile ilgili değişiklik yaparken hangi protokol sürümüne
  (eski/PIYA yeni) göre çalıştığını netleştir — geçiş süreci devam ediyor,
  iki protokol bir arada olabilir

## Konvansiyonlar
- Minimal, cerrahi değişiklik tercih edilir: dosyanın tamamını yeniden yazma,
  sadece değişen kısmı/satırları göster
- Mevcut kod stiline sadık kal
- API değişikliği yapılıyorsa versiyon (/api/v1) ve geriye dönük uyumluluk
  göz önünde bulundurulmalı
- Test yazarken in-memory SQLite izolasyonunu koru, gerçek MySQL'e
  bağlanan test yazma

## Bilinen Zayıf Nokta
Frontend/UI tarafı (Vue component, Tailwind layout detayları) zayıf nokta.
Bu tür işlerde varsayılan tasarım kararı almadan önce kısa seçenekler sun.

## Yapma
- Tüm dosyayı yeniden yazıp "değişen satırlar" diye sunma — sadece diff göster
- dealer guard ve Sanctum guard'ı karıştırma / belirsiz bırakma
- Onay almadan migration/şema değişikliği yapma
- PIYA protokol geçişiyle ilgili firmware tarafının cevabını varsayma —
  bekleyen kısımları "tamamlandı" gibi sunma
