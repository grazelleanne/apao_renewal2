import test from 'node:test';
import assert from 'node:assert/strict';
import '../../public/js/staff_report_filter.js';

const StaffReportFilter = globalThis.StaffReportFilter;

const personnel = [
  { itemNumber: 1, approvedStatus: 'new', dateOfValidity: '2026-09-14' },
  { itemNumber: 2, approvedStatus: 'renewed', dateOfValidity: '2026-09-30 00:00:00' },
  { itemNumber: 3, approvedStatus: 'NEW', dateOfValidity: '2026-10-01T00:00:00.000Z' },
  { itemNumber: 4, approvedStatus: 'new', dateOfValidity: null },
];

test('filters validity dates inclusively and normalizes database timestamps', () => {
  const result = StaffReportFilter.filter(personnel, {
    from: '2026-09-14',
    to: '2026-09-30',
    status: '',
  });

  assert.equal(result.error, null);
  assert.deepEqual(result.rows.map((record) => record.itemNumber), [1, 2]);
});

test('applies a case-insensitive status filter with a date range', () => {
  const result = StaffReportFilter.filter(personnel, {
    from: '2026-09-14',
    to: '2026-10-01',
    status: 'new',
  });

  assert.equal(result.error, null);
  assert.deepEqual(result.rows.map((record) => record.itemNumber), [1, 3]);
});

test('excludes missing validity dates only when a date boundary is selected', () => {
  assert.equal(StaffReportFilter.filter(personnel, {}).rows.length, 4);
  assert.deepEqual(
    StaffReportFilter.filter(personnel, { from: '2026-09-01' }).rows.map((record) => record.itemNumber),
    [1, 2, 3]
  );
});

test('rejects an inverted date range', () => {
  const result = StaffReportFilter.filter(personnel, {
    from: '2026-10-01',
    to: '2026-09-14',
  });

  assert.deepEqual(result.rows, []);
  assert.equal(result.error, 'Date From must be on or before Date To.');
});

test('rejects impossible calendar dates', () => {
  assert.equal(StaffReportFilter.normalizeDate('2026-02-31'), null);
});
