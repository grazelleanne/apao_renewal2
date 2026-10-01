import test from 'node:test';
import assert from 'node:assert/strict';
import '../../public/js/single_tab_guard.js';

const guard = globalThis.ApaoSingleTabGuard;

test('detects another live authenticated tab', () => {
  const owner = JSON.stringify({ tabId: 'first-tab', expiresAt: 9000 });
  assert.equal(guard.hasActiveConflict(owner, 'second-tab', 5000), true);
});

test('allows the current tab and ignores expired ownership', () => {
  const liveOwner = JSON.stringify({ tabId: 'first-tab', expiresAt: 9000 });
  const expiredOwner = JSON.stringify({ tabId: 'first-tab', expiresAt: 4000 });

  assert.equal(guard.hasActiveConflict(liveOwner, 'first-tab', 5000), false);
  assert.equal(guard.hasActiveConflict(expiredOwner, 'second-tab', 5000), false);
});

test('keeps one tab identifier for navigation in the same tab', () => {
  const values = new Map();
  const storage = {
    getItem: key => values.get(key) || null,
    setItem: (key, value) => values.set(key, value),
  };

  assert.equal(guard.getTabId(storage, () => 'stable-tab'), 'stable-tab');
  assert.equal(guard.getTabId(storage, () => 'different-tab'), 'stable-tab');
});
