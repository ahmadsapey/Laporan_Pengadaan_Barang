const data = require('./data_monitoring.json');
const p14293 = data.filter(x => x.Project_Code === 'P-14293');

console.log('=== DETAIL SEMUA DATA P-14293 ===\n');
console.log(`Total: ${p14293.length} baris\n`);

p14293.forEach((x, i) => {
  console.log(`${i + 1}. PR: ${x.PR_No}, PO: ${x.PO_No}`);
  console.log(`   Payment: "${x.PO_Payment || '[KOSONG]'}"`);
});

console.log('\n\n=== SUMMARY ===');
const payments = {};
p14293.forEach(x => {
  const status = x.PO_Payment || '[KOSONG]';
  payments[status] = (payments[status] || 0) + 1;
});

Object.entries(payments).sort((a,b) => b[1] - a[1]).forEach(([status, count]) => {
  console.log(`${status}: ${count}`);
});
