import test from 'node:test';
import assert from 'node:assert/strict';

let exportedBlob;
let downloadedFilename;

globalThis.window = globalThis;
globalThis.document = {
  createElement() {
    return {
      set href(value) {},
      set download(value) { downloadedFilename = value; },
      click() {},
    };
  },
};
globalThis.URL.createObjectURL = (blob) => {
  exportedBlob = blob;
  return 'blob:personnel-export';
};
globalThis.URL.revokeObjectURL = () => {};

await import('../../public/js/rpcsp_excel.js');

test('personnel Excel export keeps readable fields aligned and excludes binary data', async () => {
  globalThis.exportPersonnelListExcel({
    filename: 'personnel.xls',
    generatedDate: 'September 30, 2026',
    rows: [{
      itemNumber: 22,
      dateOfValidity: '2026-09-09',
      approvedStatus: 'new',
      rank: 'MAJ',
      lastName: 'Salvador & Sons',
      firstName: 'Ian',
      qtyAmmo: 33,
      photo: 'data:image/jpeg;base64,BINARY_PHOTO_DATA',
      signature: 'data:image/png;base64,BINARY_SIGNATURE_DATA',
    }],
  });

  const workbook = await exportedBlob.text();

  assert.equal(downloadedFilename, 'personnel.xls');
  assert.match(workbook, /ss:ExpandedColumnCount="16"/);
  assert.match(workbook, /ss:ExpandedRowCount="6"/);
  assert.match(workbook, /LIST OF PERSONNEL/);
  assert.match(workbook, /Salvador &amp; Sons/);
  assert.doesNotMatch(workbook, /BINARY_PHOTO_DATA|BINARY_SIGNATURE_DATA/);
  assert.doesNotMatch(workbook, />PHOTO<|>SIGNATURE</);
});

test('renewal report export uses formatted headings and a readable validity period', async () => {
  globalThis.exportPersonnelRenewalExcel({
    filename: 'renewal-report.xls',
    generatedDate: 'September 30, 2026',
    period: '2026-09-01 to 2026-09-30 | Status: Renewed',
    rows: [{
      itemNumber: 21,
      dateOfValidity: '2026-09-30',
      status: 'Renewed',
      lastName: 'Cullaman',
      firstName: 'Richard',
      middleName: 'D',
      afpSerialNumber: '858584',
      dateOfBirth: '1990-01-01',
      pistolNomenclature: 'Pistol Cal .45',
      pistolSerialNumber: 'PXA-24081',
      qtyAmmo: 40,
    }],
  });

  const workbook = await exportedBlob.text();

  assert.equal(downloadedFilename, 'renewal-report.xls');
  assert.match(workbook, /DATE OF VALIDITY/);
  assert.match(workbook, /2026-09-01 to 2026-09-30 \| Status: Renewed/);
  assert.doesNotMatch(workbook, /dateOfValidity|approvedStatus/);
});

test('audit log export uses a formatted layout and readable details', async () => {
  globalThis.exportAuditLogExcel({
    filename: 'audit-log.xls',
    generatedDate: 'September 30, 2026, 10:30 AM',
    period: '2026-09-01 to 2026-09-30',
    rows: [{
      number: 1,
      createdAt: 'Sep 29, 2026, 08:15:00 AM',
      userName: 'Admin',
      userRole: 'admin',
      action: 'login_failed',
      target: 'admin@apao.test',
      ipAddress: '127.0.0.1',
      details: { message: 'Failed login attempt', remaining_attempts: 4 },
    }],
  });

  const workbook = await exportedBlob.text();

  assert.equal(downloadedFilename, 'audit-log.xls');
  assert.match(workbook, /ss:ExpandedColumnCount="8"/);
  assert.match(workbook, /AUDIT LOG/);
  assert.match(workbook, /DATE &amp; TIME/);
  assert.match(workbook, /Message: Failed login attempt; Remaining Attempts: 4/);
  assert.match(workbook, /SplitHorizontal>5/);
  assert.doesNotMatch(workbook, /\{&quot;message&quot;|remaining_attempts/);
});
