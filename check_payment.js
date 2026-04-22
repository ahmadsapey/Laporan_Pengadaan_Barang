const data = require('./data_monitoring.json');
const p14293 = data.filter(x => x.Project_Code === 'P-14293');
const payments = {};

p14293.forEach(x => {
  const status = x.PO_Payment ? x.PO_Payment.toLowerCase().trim() : '[KOSONG]';
  payments[status] = (payments[status] || 0) + 1;
});

console.log('Status PO_Payment di P-14293:');
Object.entries(payments).sort((a,b) => b[1] - a[1]).forEach(([status, count]) => {
  console.log(`  "${status}": ${count}`);
});

console.log('\n\nDetil data dengan "submit":');
p14293.filter(x => x.PO_Payment && x.PO_Payment.toLowerCase().includes('submit')).forEach(x => {
  console.log(`  PR: ${x.PR_No}, PO: ${x.PO_No}, Payment: "${x.PO_Payment}"`);
});
