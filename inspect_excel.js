const XLSX = require('xlsx');
const path = require('path');

const filePath = 'c:\\xampp\\htdocs\\PIMS\\Office Supplies 2026 (1) (1).xlsx';

try {
    const workbook = XLSX.readFile(filePath);
    const sheetName = workbook.SheetNames[0];
    const sheet = workbook.Sheets[sheetName];

    console.log('--- Sheets Found ---', workbook.SheetNames);

    function getHeadersAtRow(rowIdx) {
        const json = XLSX.utils.sheet_to_json(sheet, { range: rowIdx, header: 1 });
        return json[0] || [];
    }

    for (let i = 0; i < 5; i++) {
        console.log(`\n--- Row ${i} Headers ---`);
        const h = getHeadersAtRow(i);
        console.log(JSON.stringify(h, null, 2));
    }

    const firstData = XLSX.utils.sheet_to_json(sheet, { range: 0 }).slice(0, 5);
    console.log('\n--- Preview Data (Range 0) ---');
    console.log(JSON.stringify(firstData, null, 2));

} catch (e) {
    console.error('Error:', e.message);
}
