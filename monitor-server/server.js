const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3000;
const DB_FILE = path.join(__dirname, 'database.json');
const API_KEY = 'ALMA-SECURITY-SECRET-KEY';

app.use(cors());
app.use(bodyParser.json());

// Initialize database
if (!fs.existsSync(DB_FILE)) {
    fs.writeFileSync(DB_FILE, JSON.stringify({ sites: {} }, null, 2));
}

// Middleware for authentication
const authenticate = (req, res, next) => {
    const authHeader = req.headers.authorization;
    if (authHeader === 'Bearer ' + API_KEY) {
        next();
    } else {
        res.status(401).json({ error: 'Unauthorized' });
    }
};

// REST API to receive site data
app.post('/site-data', authenticate, (req, res) => {
    const data = req.body;
    const db = JSON.parse(fs.readFileSync(DB_FILE));

    db.sites[data.domain] = {
        ...data,
        last_connection: new Date().toISOString()
    };

    fs.writeFileSync(DB_FILE, JSON.stringify(db, null, 2));
    console.log('Updated data for: ' + data.domain);
    res.json({ success: true });
});

// Endpoint to get all sites (for dashboard)
app.get('/sites', (req, res) => {
    const db = JSON.parse(fs.readFileSync(DB_FILE));
    res.json(Object.values(db.sites));
});

// Serve Dashboard
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'dashboard.html'));
});

app.listen(PORT, () => {
    console.log('Monitoring Server running at http://localhost:' + PORT);
});
