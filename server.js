const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const fs = require('fs');
const path = require('path');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3000;
const DB_FILE = path.join(__dirname, 'monitoring.db');
const JSON_FILE = path.join(__dirname, 'data_monitoring.json');
const META_FILE = path.join(__dirname, 'meta.json');
const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASS || 'admin123';
const ADMIN_TOKEN = process.env.ADMIN_TOKEN || 'dashboard-admin-token-2026';

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(__dirname));

function readJsonFile(filePath) {
  try {
    const content = fs.readFileSync(filePath, 'utf8');
    return JSON.parse(content);
  } catch (err) {
    return null;
  }
}

function writeMeta(meta) {
  fs.writeFileSync(META_FILE, JSON.stringify(meta, null, 2), 'utf8');
}

function ensureMetaFile() {
  const defaultMeta = {
    update_time: new Date().toISOString(),
    update_note: 'Database dibuat dari data JSON saat server pertama kali dijalankan.',
    admin: 'system'
  };
  if (!fs.existsSync(META_FILE)) {
    writeMeta(defaultMeta);
  }
}

function initDatabase() {
  if (fs.existsSync(DB_FILE)) {
    return new sqlite3.Database(DB_FILE);
  }

  const sourceData = readJsonFile(JSON_FILE);
  if (!Array.isArray(sourceData)) {
    console.error('Gagal membaca data_monitoring.json. Pastikan file JSON valid.');
    process.exit(1);
  }

  const db = new sqlite3.Database(DB_FILE);
  db.serialize(() => {
    db.run(`CREATE TABLE IF NOT EXISTS monitoring (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      Project_Code TEXT,
      Project_Name TEXT,
      PR_No TEXT,
      PO_No TEXT,
      PR_Date TEXT,
      Delivery_Date TEXT,
      PO_Date TEXT,
      Request_Delivery TEXT,
      Item_Name TEXT,
      Quantity TEXT,
      Invoicing_Status TEXT,
      Leadtime_PR_PO TEXT,
      Leadtime_PO_Deliv TEXT,
      ETA TEXT,
      Remark TEXT,
      Act_Request_to_PO TEXT,
      Act_PR_to_Request TEXT,
      PR_Status TEXT,
      PO_Payment TEXT
    )`);

    const insert = db.prepare(`INSERT INTO monitoring (
      Project_Code, Project_Name, PR_No, PO_No, PR_Date, Delivery_Date, PO_Date,
      Request_Delivery, Item_Name, Quantity, Invoicing_Status, Leadtime_PR_PO,
      Leadtime_PO_Deliv, ETA, Remark, Act_Request_to_PO, Act_PR_to_Request,
      PR_Status, PO_Payment
    ) VALUES (
      $Project_Code, $Project_Name, $PR_No, $PO_No, $PR_Date, $Delivery_Date, $PO_Date,
      $Request_Delivery, $Item_Name, $Quantity, $Invoicing_Status, $Leadtime_PR_PO,
      $Leadtime_PO_Deliv, $ETA, $Remark, $Act_Request_to_PO, $Act_PR_to_Request,
      $PR_Status, $PO_Payment
    )`);

    sourceData.forEach(row => {
      insert.run({
        $Project_Code: row.Project_Code || '',
        $Project_Name: row.Project_Name || '',
        $PR_No: row.PR_No || '',
        $PO_No: row.PO_No || '',
        $PR_Date: row.PR_Date || '',
        $Delivery_Date: row.Delivery_Date || '',
        $PO_Date: row.PO_Date || '',
        $Request_Delivery: row.Request_Delivery || '',
        $Item_Name: row.Item_Name || '',
        $Quantity: row.Quantity || '',
        $Invoicing_Status: row.Invoicing_Status || '',
        $Leadtime_PR_PO: row.Leadtime_PR_PO || '',
        $Leadtime_PO_Deliv: row.Leadtime_PO_Deliv || '',
        $ETA: row.ETA || '',
        $Remark: row.Remark || '',
        $Act_Request_to_PO: row.Act_Request_to_PO || '',
        $Act_PR_to_Request: row.Act_PR_to_Request || '',
        $PR_Status: row.PR_Status || '',
        $PO_Payment: row.PO_Payment || ''
      });
    });

    insert.finalize();
  });

  writeMeta({
    update_time: new Date().toISOString(),
    update_note: 'Database dibuat dari data JSON saat server pertama kali dijalankan.',
    admin: 'system'
  });

  return db;
}

function requireAuth(req, res, next) {
  const authHeader = req.headers.authorization || '';
  const token = authHeader.replace('Bearer ', '').trim();
  if (token === ADMIN_TOKEN) {
    return next();
  }
  res.status(401).json({ error: 'Unauthorized' });
}

const db = initDatabase();
ensureMetaFile();

app.get('/api/monitoring', (req, res) => {
  db.all('SELECT * FROM monitoring ORDER BY id', [], (err, rows) => {
    if (err) {
      return res.status(500).json({ error: 'Gagal membaca data dari database.' });
    }
    res.json(rows);
  });
});

app.get('/api/metadata', (req, res) => {
  const meta = readJsonFile(META_FILE);
  if (meta) {
    return res.json(meta);
  }
  res.json({ update_time: '', update_note: '', admin: '' });
});

app.post('/api/admin/login', (req, res) => {
  const { username, password } = req.body;
  if (username === ADMIN_USER && password === ADMIN_PASS) {
    return res.json({ token: ADMIN_TOKEN, admin: ADMIN_USER });
  }
  res.status(401).json({ error: 'Nama pengguna atau kata sandi salah.' });
});

app.post('/api/monitoring/:id', requireAuth, (req, res) => {
  const id = Number(req.params.id);
  const updateFields = {
    PR_Status: req.body.PR_Status || '',
    PO_Payment: req.body.PO_Payment || '',
    Remark: req.body.Remark || '',
    ETA: req.body.ETA || ''
  };

  const sql = `UPDATE monitoring SET
    PR_Status = $PR_Status,
    PO_Payment = $PO_Payment,
    Remark = $Remark,
    ETA = $ETA
    WHERE id = $id`;

  db.run(sql, { ...updateFields, $id: id }, function (err) {
    if (err) {
      return res.status(500).json({ error: 'Gagal mengupdate data.' });
    }
    if (this.changes === 0) {
      return res.status(404).json({ error: 'Data tidak ditemukan.' });
    }

    const meta = {
      update_time: new Date().toISOString(),
      update_note: `Record ID ${id} diperbarui oleh admin.`,
      admin: ADMIN_USER
    };
    writeMeta(meta);
    res.json({ success: true, meta });
  });
});

app.listen(PORT, () => {
  console.log(`Server berjalan di http://localhost:${PORT}`);
  console.log(`Akses admin di http://localhost:${PORT}/admin.html`);
});
