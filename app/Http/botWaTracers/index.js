// index.js - Simple WA Bot dengan HTTP API
const makeWASocket = require("@whiskeysockets/baileys").default;
const { useMultiFileAuthState, fetchLatestBaileysVersion, DisconnectReason } = require("@whiskeysockets/baileys");
const qrcodeTerm = require("qrcode-terminal");
const QRCode = require("qrcode");
const P = require("pino");
const express = require('express');
const bodyParser = require('body-parser');

require("dotenv").config();

// ===== Express App untuk HTTP API =====
const app = express();
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Variable global untuk socket WA
let globalSock = null;

// ===== Helpers =====
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const lastSent = new Map();
const MIN_GAP = 2200; // ms minimal jeda antar pesan per JID

// ---- Helper kirim WA =====
async function sendWAMessage(phone, message) {
  if (!globalSock) {
    throw new Error('WhatsApp not connected');
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
  if (!globalSock) {
    throw new Error('WhatsApp not connected');
  }
  
  try {
    const groups = await globalSock.groupFetchAllParticipating();
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
  if (!globalSock) {
    throw new Error('WhatsApp not connected');
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
      sendWAPresensi: 'POST /api/send-wa-presensi'
    }
  });
});

app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    wa_connected: globalSock ? true : false,
    timestamp: new Date().toISOString()
  });
});

app.get('/api/groups', async (req, res) => {
  try {
    if (!globalSock) {
      return res.status(503).json({ 
        success: false, 
        error: 'WhatsApp not connected. Please scan QR code first.' 
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
    
    if (!globalSock) {
      return res.status(503).json({ 
        success: false, 
        error: 'WhatsApp not connected. Please scan QR code first.' 
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
      if (distance !== undefined) {
        message += `📏 Jarak Lokasi dari Lokasi Penugasan: ${distance} Meter`;
      }
    } else if (action === 'clockOut') {
      message = `*🕒 Clock Out - ${nama_cvsr}*\n\n`;
      message += `📅 Tanggal: ${tanggal}\n`;
      message += `🕐 Pukul: ${jam} WIB\n`;
      message += `📍 Status: ${status}\n`;
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
    
    // Kirim ke personal number
    try {
      if ((action === 'clockIn' || action === 'clockOut') && foto_base64 && foto_mime) {
        try {
          const buffer = Buffer.from(foto_base64, 'base64');
          const mediaType = foto_mime || 'image/png';
          
          console.log(`[PRESENSI-API] Sending photo with caption to personal ${jid}`);
          await globalSock.sendMessage(jid, {
            image: buffer,
            caption: message,
            mimetype: mediaType
          });
        } catch (photoError) {
          console.error('[PRESENSI-API] Error sending photo:', photoError);
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
      hasError = true;
      sentTo.push({ type: 'personal', target: jid, status: 'failed', error: personalError.message });
    }
    
    // Kirim ke grup "All MyAds canvasser Team"
    try {
      // Prioritas: gunakan GROUP_ID jika tersedia, fallback ke GROUP_NAME
      const groupId = process.env.WA_GROUP_ID;
      const groupName = process.env.WA_GROUP_NAME || 'All MyAds Canvasser Team';
      const groupTarget = groupId || groupName;
      
      if ((action === 'clockIn' || action === 'clockOut') && foto_base64 && foto_mime) {
        try {
          const buffer = Buffer.from(foto_base64, 'base64');
          const mediaType = foto_mime || 'image/png';
          
          console.log(`[PRESENSI-API] Sending photo with caption to group`);
          await sendWAMessageToGroup(groupTarget, message, buffer, mediaType);
        } catch (groupPhotoError) {
          console.error('[PRESENSI-API] Error sending photo to group:', groupPhotoError.message);
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

// Start Express server
const PORT = process.env.WA_BOT_PORT || 3000;
app.listen(PORT, () => {
  console.log(`✅ HTTP API Server running on http://localhost:${PORT}`);
  console.log(`📍 Health check: http://localhost:${PORT}/api/health`);
  console.log(`📍 List groups: http://localhost:${PORT}/api/groups`);
  console.log(`📍 Send WA: POST http://localhost:${PORT}/api/send-wa`);
  console.log(`📍 Send WA Presensi: POST http://localhost:${PORT}/api/send-wa-presensi`);
});

// ===== WA Boot =====
async function start() {
  const { state, saveCreds } = await useMultiFileAuthState("./auth");
  const { version } = await fetchLatestBaileysVersion();

  const sock = makeWASocket({
    version,
    logger: P({ level: "error" }), // Kurangi log warning
    auth: state,
    syncFullHistory: true, // Sync history untuk koneksi lebih stabil
    retryRequestDelayMs: 10000, // Retry delay 10 detik
    maxMsgsInMemory: 300, // Limit buffer
  });

  globalSock = sock;
  sock.ev.on("creds.update", saveCreds);

  sock.ev.on("connection.update", async ({ connection, lastDisconnect, qr }) => {
    try {
      if (qr) {
        qrcodeTerm.generate(qr, { small: true });
        await QRCode.toFile("qr.png", qr);
        await QRCode.toFile("qr.jpg", qr, { type: "jpeg" });
        console.log("✅ QR disimpan: qr.png & qr.jpg — scan dari HP untuk login");
      }
      if (connection === "open") {
        console.log("✅ WA connected!");
      }
      if (connection === "close") {
        const code = lastDisconnect?.error?.output?.statusCode;
        const shouldReconnect = code !== DisconnectReason.loggedOut && code !== 401;
        console.log("🔌 connection closed:", code, "reconnect:", shouldReconnect);
        if (shouldReconnect) start();
      }
    } catch (e) {
      console.error("connection.update error:", e);
    }
  });

  // Handle incoming messages (optional: just log, don't reply)
  sock.ev.on("messages.upsert", async ({ messages }) => {
    if (!messages || !messages.length) return;
    for (const m of messages) {
      try {
        if (m.key?.fromMe) continue;
        const jid = m.key?.remoteJid;
        const text = m.message?.conversation || m.message?.extendedTextMessage?.text || '';
        console.log(`[📨] Message from ${jid}: ${text}`);
      } catch (e) {
        console.error("message handler error:", e);
      }
    }
  });
}

start().catch(console.error);
