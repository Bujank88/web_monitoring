// index.js - Simple WA Bot dengan HTTP API
const makeWASocket = require("@whiskeysockets/baileys").default;
const { useMultiFileAuthState, fetchLatestBaileysVersion, DisconnectReason } = require("@whiskeysockets/baileys");
const qrcodeTerm = require("qrcode-terminal");
const QRCode = require("qrcode");
const P = require("pino");
const express = require('express');
const bodyParser = require('body-parser');
const readline = require('readline');

require("dotenv").config();

// ===== Express App untuk HTTP API =====
const app = express();
app.use(bodyParser.json({ limit: '50mb' }));
app.use(bodyParser.urlencoded({ limit: '50mb', extended: true }));

// Variable global untuk socket WA
let globalSock = null;
let isConnecting = false; // Flag untuk cegah multiple reconnect
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;
let lastReconnectTime = 0;
const RECONNECT_DELAY = 5000; // 5 detik minimum antar reconnect

// Helper: Check if WA is ready to send messages
function isWAReady() {
  if (!globalSock) {
    console.log('[WA-READY-CHECK] globalSock is null');
    return false;
  }
  if (isConnecting) {
    console.log('[WA-READY-CHECK] Still connecting');
    return false;
  }
  if (!globalSock.user) {
    console.log('[WA-READY-CHECK] Socket not authenticated (no user)');
    return false;
  }
  console.log('[WA-READY-CHECK] ✅ Ready to send');
  return true;
}

// Helper: Wait for connection to be ready with timeout
async function waitForReady(maxWaitMs = 10000) {
  const startTime = Date.now();
  while (!isWAReady()) {
    if (Date.now() - startTime > maxWaitMs) {
      throw new Error('Timeout waiting for WhatsApp connection');
    }
    await sleep(500);
  }
  return true;
}

// Auth method: 'qr' atau 'pairing'
// Set via env: AUTH_METHOD=pairing atau AUTH_METHOD=qr
const AUTH_METHOD = process.env.AUTH_METHOD || 'qr';
const PHONE_NUMBER = process.env.PHONE_NUMBER || ''; // Format: 62xxxx (tanpa +)

// Helper untuk input dari console
const question = (q) => {
  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
  });
  return new Promise((resolve) => {
    rl.question(q, (answer) => {
      rl.close();
      resolve(answer);
    });
  });
};

// ===== Helpers =====
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const lastSent = new Map();
const MIN_GAP = 2200; // ms minimal jeda antar pesan per JID

// ---- Helper kirim WA =====
async function sendWAMessage(phone, message) {
  // Check if ready, wait up to 10 seconds
  try {
    await waitForReady(10000);
  } catch (e) {
    throw new Error('WhatsApp not ready: ' + e.message);
  }
  
  let jid = phone;
  if (!jid.includes('@')) {
    jid = jid.replace(/\D/g, '');
    if (!jid.startsWith('62')) {
      if (jid.startsWith('0')) {
        jid = '62' + jid.substring(1);
      } else if (jid.startsWith('8')) {
        jid = '62' + jid;
      }
    }
    jid = jid + '@s.whatsapp.net';
  }
  
  // Validasi JID format
  if (!jid.includes('@')) {
    throw new Error(`Invalid JID format: ${jid}`);
  }
  
  console.log(`[WA-API] Sending to ${jid}: ${message.substring(0, 50)}...`);
  await globalSock.sendMessage(jid, { text: message });
  
  return { success: true, jid, message: 'Message sent' };
}

// ---- Helper cari grup berdasarkan nama =====
async function findGroupByName(groupName) {
  // Check if ready, wait up to 10 seconds
  try {
    await waitForReady(10000);
  } catch (e) {
    throw new Error('WhatsApp not ready: ' + e.message);
  }
  
  try {
    const groups = await globalSock.groupFetchAllParticipating();
    if (!groups || Object.keys(groups).length === 0) {
      console.log(`[GROUP-FINDER] No groups available`);
      return null;
    }
    console.log(`[GROUP-FINDER] Available groups: ${Object.values(groups).map(g => g.subject).join(', ')}`);
    
    for (const group of Object.values(groups)) {
      if (group.subject && group.subject.toLowerCase().includes(groupName.toLowerCase())) {
        console.log(`[GROUP-FINDER] Found group: ${group.subject} (${group.id})`);
        return group;
      }
    }
    console.log(`[GROUP-FINDER] Group "${groupName}" not found`);
    return null;
  } catch (error) {
    console.error('[GROUP-FINDER] Error finding group:', error);
    return null;
  }
}

// ---- Helper kirim ke grup dengan support Group ID =====
async function sendWAMessageToGroup(groupNameOrId, message, mediaBuffer = null, mediaMimeType = null) {
  // Check if ready, wait up to 10 seconds
  try {
    await waitForReady(10000);
  } catch (e) {
    throw new Error('WhatsApp not ready: ' + e.message);
  }
  
  let groupId = null;
  let groupSubject = null;
  
  // Jika input berupa JID format (berakhir dengan @g.us), gunakan langsung
  if (groupNameOrId.includes('@g.us')) {
    groupId = groupNameOrId;
    console.log(`[GROUP-SEND] Using direct group ID: ${groupId}`);
  } else {
    // Cari berdasarkan nama
    const group = await findGroupByName(groupNameOrId);
    if (!group) {
      throw new Error(`Group "${groupNameOrId}" not found`);
    }
    groupId = group.id;
    groupSubject = group.subject;
  }
  
  console.log(`[GROUP-SEND] Sending to group ${groupSubject || groupId}`);
  
  if (mediaBuffer && mediaMimeType) {
    await globalSock.sendMessage(groupId, {
      image: mediaBuffer,
      caption: message,
      mimetype: mediaMimeType
    });
  } else {
    await globalSock.sendMessage(groupId, { text: message });
  }
  
  return { success: true, groupId, groupSubject, message: 'Message sent to group' };
}

// ===== HTTP API Endpoints =====
app.get('/', (req, res) => {
  res.json({ 
    message: 'WhatsApp Bot API is running',
    endpoints: {
      health: '/api/health',
      groups: '/api/groups',
      sendWA: 'POST /api/send-wa',
      sendWAPresensi: 'POST /api/send-wa-presensi',
      sendWALogbook: 'POST /api/send-wa-logbook'
    }
  });
});

app.get('/api/health', (req, res) => {
  const ready = isWAReady();
  res.json({ 
    status: ready ? 'ok' : 'not-ready',
    wa_connected: globalSock ? true : false,
    is_authenticated: globalSock?.user ? true : false,
    is_connecting: isConnecting,
    reconnect_attempts: reconnectAttempts,
    user_jid: globalSock?.user?.id || null,
    timestamp: new Date().toISOString()
  });
});

app.get('/api/groups', async (req, res) => {
  try {
    if (!isWAReady()) {
      return res.status(503).json({ 
        success: false, 
        error: 'WhatsApp not ready. Please wait for connection.' 
      });
    }
    
    const groups = await globalSock.groupFetchAllParticipating();
    const groupList = Object.values(groups).map(g => ({
      id: g.id,
      subject: g.subject,
      participantsCount: g.participants ? g.participants.length : 0
    }));
    
    res.json({ 
      success: true, 
      groups: groupList,
      total: groupList.length
    });
  } catch (error) {
    res.status(500).json({ 
      success: false, 
      error: error.message 
    });
  }
});

app.post('/api/send-wa', async (req, res) => {
  try {
    const { phone, message, nama_akun, email, password, uuid } = req.body;
    
    if (!phone) {
      return res.status(400).json({ 
        success: false, 
        error: 'phone is required' 
      });
    }
    
    if (!message && !nama_akun) {
      return res.status(400).json({ 
        success: false, 
        error: 'either message or nama_akun is required' 
      });
    }
    
    if (!isWAReady()) {
      return res.status(503).json({ 
        success: false, 
        error: 'WhatsApp not ready. Please wait for connection or scan QR/pairing code.' 
      });
    }
    
    let finalMessage = message || '';
    if (nama_akun && email && password) {
      finalMessage = `*🎉 Akun Panen Poin Telah Dibuat!* 🎉\n\n`;
      finalMessage += `Halo *${nama_akun}*,\n\n`;
      finalMessage += `Akun Panen Poin Anda telah berhasil dibuat!\n\n`;
      finalMessage += `📧 *Email:* ${email}\n`;
      finalMessage += `🔑 *Password:* ${password}\n`;
      if (uuid) {
        finalMessage += `🆔 *UUID:* ${uuid}\n`;
      }
      finalMessage += `\n*🚀 Langkah Selanjutnya:*\n`;
      finalMessage += `1️⃣ Kunjungi: https://panenpoin-myads.com/\n`;
      finalMessage += `2️⃣ Login dengan email & password di atas\n`;
      finalMessage += `3️⃣ Cek saldo poin di dashboard\n`;
      finalMessage += `4️⃣ Tingkatkan transaksi untuk tambah poin\n`;
      finalMessage += `5️⃣ Semakin banyak transaksi = Semakin banyak poin! 💰\n\n`;
      finalMessage += `⚠️ *PENTING:*\n`;
      finalMessage += `• Jangan bagikan password kepada siapapun\n`;
      finalMessage += `• Ubah password setelah login pertama\n\n`;
      finalMessage += `Terima kasih! 🙏`;
    }
    
    const result = await sendWAMessage(phone, finalMessage);
    console.log(`[API] Message sent to ${phone}`);
    res.json(result);
    
  } catch (error) {
    console.error('[API] Error sending message:', error);
    res.status(500).json({ 
      success: false, 
      error: error.message 
    });
  }
});

// ===== ENDPOINT UNTUK PRESENSI (Clock In / Clock Out) =====
app.post('/api/send-wa-presensi', async (req, res) => {
  try {
    const { 
      phone, nama_cvsr, action, tanggal, jam, status, latitude, longitude, 
      foto_base64, foto_mime, lokasi_penugasan_lat, lokasi_penugasan_lng, 
      lokasi_penugasan_nama, distance, keterangan, tipe_izin
    } = req.body;
    
    if (!phone) {
      return res.status(400).json({ 
        success: false, 
        error: 'phone is required' 
      });
    }
    
    if (!nama_cvsr || !action) {
      return res.status(400).json({ 
        success: false, 
        error: 'nama_cvsr and action (clockIn/clockOut/izin) are required' 
      });
    }
    
    if (!globalSock) {
      return res.status(503).json({ 
        success: false, 
        error: 'WhatsApp not connected. Please scan QR code first.' 
      });
    }
    
    // Format message berdasarkan action
    let message = '';
    
    if (action === 'clockIn') {
      message = `*🕐 Clock In - ${nama_cvsr}*\n\n`;
      message += `📅 Tanggal: ${tanggal}\n`;
      message += `🕐 Pukul: ${jam} WIB\n`;
      message += `📍 Status: ${status}\n`;
      message += `📌 Lokasi: ${parseFloat(latitude).toFixed(6)}, ${parseFloat(longitude).toFixed(6)}\n`;
      if (lokasi_penugasan_lat && lokasi_penugasan_lng) {
        message += `🗺️ Lokasi Penugasan Koordinat: ${parseFloat(lokasi_penugasan_lat).toFixed(6)}, ${parseFloat(lokasi_penugasan_lng).toFixed(6)}\n`;
      }
      if (distance !== undefined) {
        message += `📏 Jarak Lokasi dari Lokasi Penugasan: ${distance} Meter`;
      }
    } else if (action === 'clockOut') {
      message = `*🕒 Clock Out - ${nama_cvsr}*\n\n`;
      message += `📅 Tanggal: ${tanggal}\n`;
      message += `🕐 Pukul: ${jam} WIB\n`;
      message += `📍 Status: ${status}\n`;
      message += `📌 Lokasi: ${parseFloat(latitude).toFixed(6)}, ${parseFloat(longitude).toFixed(6)}\n`;
      if (lokasi_penugasan_lat && lokasi_penugasan_lng) {
        message += `🗺️ Lokasi Penugasan Koordinat: ${parseFloat(lokasi_penugasan_lat).toFixed(6)}, ${parseFloat(lokasi_penugasan_lng).toFixed(6)}\n`;
      }
      if (distance !== undefined) {
        message += `📏 Jarak Lokasi dari Lokasi Penugasan: ${distance} Meter`;
      }
    } else if (action === 'izin') {
      const emojiIzin = tipe_izin === 'Sakit' ? '🤒' : '📋';
      message = `*${emojiIzin} ${tipe_izin} - ${nama_cvsr}*\n\n`;
      message += `📅 Tanggal: ${tanggal}\n`;
      message += `📝 Keterangan: ${keterangan || '-'}`;
    }
    
    // Siapkan JID untuk personal message
    let jid = phone;
    if (!jid.includes('@')) {
      jid = jid.replace(/\D/g, '');
      if (!jid.startsWith('62')) {
        if (jid.startsWith('0')) {
          jid = '62' + jid.substring(1);
        } else if (jid.startsWith('8')) {
          jid = '62' + jid;
        }
      }
      jid = jid + '@s.whatsapp.net';
    }
    
    // Validasi JID
    if (!jid || !jid.includes('@')) {
      return res.status(400).json({ 
        success: false, 
        error: `Invalid phone format: ${phone}. Expected format: 62XXXXXXXXXX or 0XXXXXXXXXX` 
      });
    }
    
    console.log(`[PRESENSI-API] Processing ${action} for ${jid}`);
    
    const sentTo = [];
    let hasError = false;
    
    // Helper: Clean base64 string (remove data URI prefix if present)
    const cleanBase64 = (b64String) => {
      if (!b64String) return b64String;
      // Remove data URI prefix like "data:image/png;base64,"
      let cleaned = b64String.replace(/^data:image\/\w+;base64,/i, '');
      // Replace spaces with +
      cleaned = cleaned.replace(/ /g, '+');
      return cleaned;
    };

    // Kirim ke personal number
    try {
      if ((action === 'clockIn' || action === 'clockOut') && foto_base64 && foto_mime) {
        try {
          const cleanedBase64 = cleanBase64(foto_base64);
          
          // Check size before sending
          const sizeInMB = (cleanedBase64.length * 0.75) / 1024 / 1024;
          console.log(`[PRESENSI-API] Image size estimate: ${sizeInMB.toFixed(2)} MB`);
          
          if (sizeInMB > 10) {
            console.warn('[PRESENSI-API] Image too large, sending text only');
            await globalSock.sendMessage(jid, { text: message + '\n\n⚠️ Foto terlalu besar untuk dikirim via WA' });
          } else {
            const buffer = Buffer.from(cleanedBase64, 'base64');
            const mediaType = foto_mime || 'image/png';
            
            console.log(`[PRESENSI-API] Sending photo with caption to personal ${jid}`);
            await globalSock.sendMessage(jid, {
              image: buffer,
              caption: message,
              mimetype: mediaType
            });
          }
        } catch (photoError) {
          console.error('[PRESENSI-API] Error sending photo:', photoError.message);
          console.error('[PRESENSI-API] Error stack:', photoError.stack);
          // Fallback: kirim text jika foto gagal
          console.log(`[PRESENSI-API] Fallback: sending text message to personal ${jid}`);
          await globalSock.sendMessage(jid, { text: message });
        }
      } else {
        // Untuk izin atau jika tidak ada foto, kirim text message
        console.log(`[PRESENSI-API] Sending text message to personal ${jid}`);
        await globalSock.sendMessage(jid, { text: message });
      }
      sentTo.push({ type: 'personal', target: jid, status: 'sent' });
    } catch (personalError) {
      console.error('[PRESENSI-API] Error sending to personal:', personalError.message);
      console.error('[PRESENSI-API] Error stack:', personalError.stack);
      hasError = true;
      sentTo.push({ type: 'personal', target: jid, status: 'failed', error: personalError.message });
    }
    
    // Kirim ke grup "All MyAds canvasser Team"
    try {
      // Add delay before sending to group (avoid rate limit)
      await sleep(1000);
      
      // Prioritas: gunakan GROUP_ID jika tersedia, fallback ke GROUP_NAME
      const groupId = process.env.WA_GROUP_ID;
      const groupName = process.env.WA_GROUP_NAME || 'All MyAds Canvasser Team';
      const groupTarget = groupId || groupName;
      
      if ((action === 'clockIn' || action === 'clockOut') && foto_base64 && foto_mime) {
        try {
          const cleanedBase64 = cleanBase64(foto_base64);
          
          // Check size before sending
          const sizeInMB = (cleanedBase64.length * 0.75) / 1024 / 1024;
          console.log(`[PRESENSI-API] Image size for group: ${sizeInMB.toFixed(2)} MB`);
          
          if (sizeInMB > 10) {
            console.warn('[PRESENSI-API] Image too large for group, sending text only');
            await sendWAMessageToGroup(groupTarget, message + '\n\n⚠️ Foto terlalu besar untuk dikirim via WA');
          } else {
            const buffer = Buffer.from(cleanedBase64, 'base64');
            const mediaType = foto_mime || 'image/png';
            
            console.log(`[PRESENSI-API] Sending photo with caption to group`);
            await sendWAMessageToGroup(groupTarget, message, buffer, mediaType);
          }
        } catch (groupPhotoError) {
          console.error('[PRESENSI-API] Error sending photo to group:', groupPhotoError.message);
          console.error('[PRESENSI-API] Error stack:', groupPhotoError.stack);
          // Fallback: kirim text ke grup
          console.log(`[PRESENSI-API] Fallback: sending text message to group`);
          await sendWAMessageToGroup(groupTarget, message);
        }
      } else {
        // Untuk izin atau jika tidak ada foto, kirim text message ke grup
        console.log(`[PRESENSI-API] Sending text message to group`);
        await sendWAMessageToGroup(groupTarget, message);
      }
      sentTo.push({ type: 'group', target: groupTarget, status: 'sent' });
    } catch (groupError) {
      console.error('[PRESENSI-API] Error sending to group:', groupError.message);
      console.error('[PRESENSI-API] Error stack:', groupError.stack);
      // Tidak fatal - personal sudah terkirim, grup optional
      const groupId = process.env.WA_GROUP_ID;
      const groupName = process.env.WA_GROUP_NAME || 'All MyAds canvasser Team';
      sentTo.push({ type: 'group', target: groupId || groupName, status: 'failed', error: groupError.message });
    }
    
    // Return error hanya jika personal gagal
    if (hasError) {
      return res.status(500).json({ 
        success: false, 
        error: 'Failed to send personal notification',
        sentTo
      });
    }
    
    res.json({ 
      success: true, 
      sentTo,
      message: `${action} notification sent` 
    });
    
  } catch (error) {
    console.error('[PRESENSI-API] Error sending message:', error);
    res.status(500).json({ 
      success: false, 
      error: error.message 
    });
  }
});

// ===== ENDPOINT UNTUK LOGBOOK =====
app.post('/api/send-wa-logbook', async (req, res) => {
  try {
    const { 
      phone, nama_canvasser, tanggal, komitmen, plan_min_topup, status, 
      metode, pembahasan, foto_base64, foto_mime, group_id,
      jam, company_name, email, regional, myads_account, mobile_phone
    } = req.body;
    
    if (!phone) {
      return res.status(400).json({ 
        success: false, 
        error: 'phone is required' 
      });
    }
    
    if (!nama_canvasser || !tanggal || !komitmen) {
      return res.status(400).json({ 
        success: false, 
        error: 'nama_canvasser, tanggal, dan komitmen are required' 
      });
    }
    
    if (!isWAReady()) {
      return res.status(503).json({ 
        success: false, 
        error: 'WhatsApp not ready. Please wait for connection or scan QR/pairing code.' 
      });
    }
    
    // Format caption message
    // Helper function untuk format angka dengan titik pemisah ribuan
    const formatCurrency = (num) => {
      if (!num) return '0';
      return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };
    
    let caption = `📋 *LOGBOOK REALISASI*\n\n`;
    caption += `👤 *Canvasser:* ${nama_canvasser}\n`;
    caption += `📅 *Tanggal:* ${tanggal}\n`;
    caption += `⏰ *Jam:* ${jam}\n`;
    caption += `💼 *Komitmen:* ${komitmen}\n`;
    caption += `💰 *Plan Min Topup:* Rp${formatCurrency(plan_min_topup)}\n`;
    caption += `📊 *Status:* ${status}\n`;
    
    // Informasi tambahan dari leads_master
    if (company_name) {
      caption += `\n🏢 *Perusahaan:* ${company_name}\n`;
    }
    if (email) {
      caption += `📧 *Email:* ${email}\n`;
    }
    if (regional) {
      caption += `🗺️ *Regional:* ${regional}\n`;
    }
    if (myads_account) {
      caption += `📱 *MyAds Account:* ${myads_account}\n`;
    }
    if (mobile_phone) {
      caption += `☎️ *No. HP:* ${mobile_phone}\n`;
    }
    console.error('[LOGBOOK-API] Additional Info:', {
      company_name, email, regional, myads_account, mobile_phone
    });
    
    if (metode) {
      caption += `\n🔄 *Metode:* ${metode === 'online' ? 'Online' : 'Offline'}\n`;
    }
    
    if (pembahasan) {
      caption += `\n💬 *Pembahasan:*\n${pembahasan}\n`;
    }

    // Prepare JID untuk personal message
    let jid = phone;
    if (!jid.includes('@')) {
      jid = jid.replace(/\D/g, '');
      if (!jid.startsWith('62')) {
        if (jid.startsWith('0')) {
          jid = '62' + jid.substring(1);
        } else if (jid.startsWith('8')) {
          jid = '62' + jid;
        }
      }
      jid = jid + '@s.whatsapp.net';
    }
    
    // Validasi JID
    if (!jid || !jid.includes('@')) {
      return res.status(400).json({ 
        success: false, 
        error: 'Invalid phone number format' 
      });
    }
    
    console.log(`[LOGBOOK-API] Processing logbook for ${jid}`);
    console.log(`[LOGBOOK-API] Received data:`, {
      phone, nama_canvasser, tanggal, jam, komitmen, plan_min_topup, status,
      company_name, email, regional, myads_account, mobile_phone,
      metode, pembahasan: pembahasan?.substring(0, 50)
    });
    
    const sentTo = [];
    let hasError = false;
    
    // Helper: Clean base64 string (remove data URI prefix if present)
    const cleanBase64 = (b64String) => {
      if (!b64String) return b64String;
      // Remove data URI prefix like "data:image/png;base64,"
      let cleaned = b64String.replace(/^data:image\/\w+;base64,/i, '');
      // Replace spaces with +
      cleaned = cleaned.replace(/ /g, '+');
      return cleaned;
    };
    
    // 1️⃣ Kirim ke personal number dengan retry
    try {
      // Check base64 size untuk avoid memory issues
      if (foto_base64) {
        const sizeInMB = (foto_base64.length * 0.75) / 1024 / 1024; // rough estimate
        console.log(`[LOGBOOK-API] Image size estimate: ${sizeInMB.toFixed(2)} MB`);
        if (sizeInMB > 10) {
          console.warn('[LOGBOOK-API] Image too large, sending text only');
          // Send as text only if too large
          await globalSock.sendMessage(jid, { text: caption + '\n\n⚠️ Foto terlalu besar untuk dikirim via WA' });
        } else {
          const cleanedBase64 = cleanBase64(foto_base64);
          const imageBuffer = Buffer.from(cleanedBase64, 'base64');
          await globalSock.sendMessage(jid, {
            image: imageBuffer,
            caption: caption,
            mimetype: foto_mime
          });
        }
      } else {
        await globalSock.sendMessage(jid, { text: caption });
      }
      sentTo.push({
        type: 'personal',
        jid: jid,
        status: 'success'
      });
      console.log(`[LOGBOOK-API] ✅ Message sent to personal: ${jid}`);
    } catch (personalError) {
      console.error('[LOGBOOK-API] Error sending to personal:', personalError.message);
      console.error('[LOGBOOK-API] Error stack:', personalError.stack);
      hasError = true;
    }
    
    // 2️⃣ Kirim ke grup (jika ada group_id) dengan retry
    if (group_id) {
      try {
        // Wait a bit before sending to group (avoid rate limit)
        await sleep(1000);
        
        if (foto_base64 && foto_mime) {
          const sizeInMB = (foto_base64.length * 0.75) / 1024 / 1024;
          if (sizeInMB > 10) {
            console.warn('[LOGBOOK-API] Image too large for group, sending text only');
            await globalSock.sendMessage(group_id, { text: caption + '\n\n⚠️ Foto terlalu besar untuk dikirim via WA' });
          } else {
            const cleanedBase64 = cleanBase64(foto_base64);
            const imageBuffer = Buffer.from(cleanedBase64, 'base64');
            await globalSock.sendMessage(group_id, {
              image: imageBuffer,
              caption: caption,
              mimetype: foto_mime
            });
          }
        } else {
          await globalSock.sendMessage(group_id, { text: caption });
        }
        sentTo.push({
          type: 'group',
          groupId: group_id,
          status: 'success'
        });
        console.log(`[LOGBOOK-API] ✅ Message sent to group: ${group_id}`);
      } catch (groupError) {
        console.error('[LOGBOOK-API] Error sending to group:', groupError.message);
        console.error('[LOGBOOK-API] Error stack:', groupError.stack);
        // Tidak set hasError karena group adalah optional
      }
    }
    
    // Return error hanya jika personal gagal
    if (hasError && sentTo.length === 0) {
      return res.status(500).json({ 
        success: false, 
        error: 'Failed to send logbook notification',
        sentTo 
      });
    }
    
    res.json({ 
      success: true, 
      sentTo,
      message: 'Logbook notification sent' 
    });
    
  } catch (error) {
    console.error('[LOGBOOK-API] Error sending message:', error);
    res.status(500).json({ 
      success: false, 
      error: error.message 
    });
  }
});

// Start Express server
const PORT = process.env.WA_BOT_PORT || 3000;
app.listen(PORT, () => {
  console.log(`✅ HTTP API Server running on http://localhost:${PORT}`);
  console.log(`📍 Health check: http://localhost:${PORT}/api/health`);
  console.log(`📍 List groups: http://localhost:${PORT}/api/groups`);
  console.log(`📍 Send WA: POST http://localhost:${PORT}/api/send-wa`);
  console.log(`📍 Send WA Presensi: POST http://localhost:${PORT}/api/send-wa-presensi`);
  console.log(`📍 Send WA Logbook: POST http://localhost:${PORT}/api/send-wa-logbook`);
});

// ===== WA Boot =====
async function start() {
  const { state, saveCreds } = await useMultiFileAuthState("./auth");
  const { version } = await fetchLatestBaileysVersion();

  const sock = makeWASocket({
    version,
    logger: P({ level: "silent" }), // Silent mode untuk hindari spam error log
    auth: state,
    syncFullHistory: false, // DISABLE sync history untuk pairing code (hindari Invalid buffer error)
    retryRequestDelayMs: 10000, // Retry delay 10 detik
    maxMsgsInMemory: 300, // Limit buffer
    printQRInTerminal: false, // Disable default QR print karena kita handle manual
    getMessage: async (key) => {
      return { conversation: 'Hello' }; // Return dummy message untuk hindari error
    },
    shouldIgnoreJid: (jid) => {
      // Ignore broadcast messages
      return jid === 'status@broadcast';
    },
    markOnlineOnConnect: false, // Jangan auto set online
  });

  globalSock = sock;
  sock.ev.on("creds.update", saveCreds);

  sock.ev.on("connection.update", async ({ connection, lastDisconnect, qr, isNewLogin }) => {
    try {
      // Handle QR Code method
      if (qr && AUTH_METHOD === 'qr') {
        console.log('\n📱 === QR CODE METHOD ===');
        qrcodeTerm.generate(qr, { small: true });
        await QRCode.toFile("qr.png", qr);
        await QRCode.toFile("qr.jpg", qr, { type: "jpeg" });
        console.log("✅ QR Code disimpan: qr.png & qr.jpg");
        console.log("📲 Scan QR dari WhatsApp di HP Anda:");
        console.log("   1. Buka WhatsApp di HP");
        console.log("   2. Tap Menu (⋮) > Linked Devices");
        console.log("   3. Tap 'Link a Device'");
        console.log("   4. Scan QR code di atas atau file qr.png\n");
      }
      
      // Handle Pairing Code method - hanya minta jika belum registered
      if (qr && AUTH_METHOD === 'pairing' && !sock.authState.creds.registered) {
        console.log('\n📞 === PAIRING CODE METHOD ===');
        let phoneNumber = PHONE_NUMBER;
        
        // Jika nomor tidak ada di env, tanya user
        if (!phoneNumber) {
          phoneNumber = await question('Masukkan nomor WhatsApp Anda (62xxxx, tanpa +): ');
        }
        
        // Validasi format nomor
        phoneNumber = phoneNumber.replace(/[^0-9]/g, ''); // Hapus non-digit
        if (!phoneNumber.startsWith('62')) {
          if (phoneNumber.startsWith('0')) {
            phoneNumber = '62' + phoneNumber.substring(1);
          } else if (phoneNumber.startsWith('8')) {
            phoneNumber = '62' + phoneNumber;
          }
        }
        
        console.log(`📱 Nomor yang digunakan: +${phoneNumber}`);
        console.log('⏳ Meminta pairing code...');
        
        try {
          // Tunggu sebentar untuk pastikan koneksi ready
          await sleep(1500);
          
          // Request pairing code
          const code = await sock.requestPairingCode(phoneNumber);
          console.log('\n✅ ═══════════════════════════════════');
          console.log('   PAIRING CODE ANDA: ' + code);
          console.log('   ═══════════════════════════════════\n');
          console.log('📲 Cara menggunakan pairing code:');
          console.log('   1. Buka WhatsApp di HP Anda');
          console.log('   2. Tap Menu (⋮) > Linked Devices');
          console.log('   3. Tap "Link a Device"');
          console.log('   4. Tap "Link with phone number instead"');
          console.log(`   5. Masukkan kode: ${code.match(/.{1,4}/g).join('-')}`);
          console.log('   6. Tunggu koneksi terhubung...\n');
        } catch (pairingError) {
          console.error('❌ Error requesting pairing code:', pairingError.message);
          console.log('💡 Coba restart aplikasi atau hapus folder ./auth untuk reset session\n');
        }
      }
      
      if (connection === "open") {
        console.log("✅ WhatsApp Connected!");
        console.log("🔐 Session tersimpan di folder ./auth");
        console.log("💡 Anda tidak perlu scan QR/pairing lagi selama file auth tidak dihapus\n");
        isConnecting = false;
        reconnectAttempts = 0; // Reset counter
        lastReconnectTime = 0; // Reset time
        console.log(`👤 Logged in as: ${sock.user?.id || 'Unknown'}`);
        console.log(`📱 Phone: ${sock.user?.name || 'Unknown'}\n`);
      }
      
      if (connection === "close") {
        const code = lastDisconnect?.error?.output?.statusCode;
        const reason = lastDisconnect?.error?.output?.payload?.error || 'Unknown';
        const shouldReconnect = code !== DisconnectReason.loggedOut && code !== 401;
        console.log("🔌 Connection closed. Status code:", code, "| Reason:", reason);
        console.log("🔄 Should reconnect:", shouldReconnect);
        
        // Null out the socket to prevent "Connection Closed" errors
        globalSock = null;
        
        if (code === DisconnectReason.loggedOut) {
          console.log("⚠️ Anda telah logout. Hapus folder ./auth dan restart untuk login ulang.");
          isConnecting = false;
          return; // Don't try to reconnect
        }
        
        if (shouldReconnect && reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
          reconnectAttempts++;
          const delay = Math.min(5000 * reconnectAttempts, 30000); // Exponential backoff, max 30s
          console.log(`⏳ Reconnecting (attempt ${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS}) in ${delay/1000} seconds...`);
          isConnecting = true;
          setTimeout(() => {
            console.log(`🔄 Starting reconnection attempt ${reconnectAttempts}...`);
            isConnecting = false;
            start().catch(err => {
              console.error('❌ Reconnect failed:', err.message);
              isConnecting = false;
            });
          }, delay);
        } else if (reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) {
          console.log("❌ Max reconnect attempts reached. Please restart the application.");
          isConnecting = false;
        }
      }
    } catch (e) {
      console.error("❌ connection.update error:", e.message);
      console.error("Error stack:", e.stack);
      isConnecting = false;
    }
  });

  // Handle incoming messages (optional: just log, don't reply)
  sock.ev.on("messages.upsert", async ({ messages }) => {
    try {
      if (!messages || !messages.length) return;
      for (const m of messages) {
        try {
          if (m.key?.fromMe) continue;
          const jid = m.key?.remoteJid;
          
          // Skip broadcast messages
          if (jid === 'status@broadcast') continue;
          
          const text = m.message?.conversation || m.message?.extendedTextMessage?.text || '';
          if (text) {
            console.log(`[📨] Message from ${jid}: ${text}`);
          }
        } catch (msgError) {
          // Silent ignore per-message errors
        }
      }
    } catch (e) {
      // Silent ignore message upsert errors to prevent "Invalid buffer" spam
    }
  });
}

start().catch(console.error);
