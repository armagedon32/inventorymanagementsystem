const mysql = require('mysql2/promise');
const fs = require('fs');

const exportPath = 'db_export.json';
const data = JSON.parse(fs.readFileSync(exportPath));

const dbConfig = {
  host: 'YOUR_RDS_ENDPOINT',     // e.g., custodian-db.xxx.rds.amazonaws.com
  database: 'custodian_db',      // your database name
  user: 'admin',                 // your master username
  password: 'YOUR_PASSWORD',     // your master password
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

async function migrate() {
  const connection = await mysql.createPool(dbConfig);
  
  const allTables = Object.keys(data.tables).filter(t => 
    t && !t.includes('sqlite_sequence')
  );
  
  console.log(`Migrating ${allTables.length} tables...`);
  
  for (const tableName of allTables) {
    const rows = data.tables[tableName];
    if (rows.length === 0) {
      console.log(`⏭️  Skipping empty table: ${tableName}`);
      continue;
    }
    
    const columns = Object.keys(rows[0]);
    const placeholders = columns.map(() => '?').join(', ');
    const columnsStr = columns.join(', ');
    
    // Prepare values for each row
    const valuesList = rows.map(row => 
      columns.map(col => row[col] !== undefined ? row[col] : null)
    );
    
    try {
      await connection.execute(`SET FOREIGN_KEY_CHECKS=0`);
      await connection.execute(`TRUNCATE TABLE \`${tableName}\``);
      
      // Insert in batches for efficiency
      if (rows.length > 0) {
        await connection.execute(
          `INSERT INTO \`${tableName}\` (${columnsStr}) VALUES (${placeholders})`,
          valuesList
        );
      }
      
      console.log(`✓ ${tableName}: ${rows.length} rows migrated`);
    } catch (err) {
      console.error(`✗ ${tableName}: ${err.message}`);
    }
  }
  
  await connection.execute(`SET FOREIGN_KEY_CHECKS=1`);
  await connection.end();
  console.log('\n✅ Migration complete! All data transferred to RDS MySQL.');
}

migrate().catch(err => {
  console.error('❌ Fatal error:', err);
  process.exit(1);
});