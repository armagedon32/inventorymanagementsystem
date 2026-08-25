const Database = require('better-sqlite3');
const fs = require('fs');
const path = require('path');

const dbPath = path.join(__dirname, 'server', 'data', 'custodian.db');
const db = new Database(dbPath);

// Get all table names from sqlite_master
const tables = db.prepare("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name").all();

const output = { tables: {}, schemas: {} };

for (const { name } of tables) {
  output.tables[name] = db.prepare(`SELECT * FROM ${name}`).all();
  const schema = db.prepare(`PRAGMA table_info(${name})`).all();
  output.schemas[name] = schema.map(c => ({ name: c.name, type: c.type }));
}

fs.writeFileSync('db_export.json', JSON.stringify(output, null, 2));
console.log('Export complete. Tables:', tables.map(t => t.name));
console.log('Exported to db_export.json');
db.close();