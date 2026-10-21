import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';

// It runs public/build/php-web.mjs: run "castor app:build" first
import { compare } from '../public/page.mjs';

const oldLock = await readFile(new URL('../data/old.json', import.meta.url), 'utf8');
const newLock = await readFile(new URL('../data/new.json', import.meta.url), 'utf8');

test('the page compares the examples', async () => {
    const html = await compare(oldLock, newLock);

    assert.deepEqual(operations(html), ['upgrade', 'upgrade', 'install', 'upgrade', 'upgrade']);
    assert.doesNotMatch(html, /^(Deprecated|Notice|Warning): /m);
});

// The page runs PHP again on each submit
test('the page compares them again, the other way around', async () => {
    const html = await compare(newLock, oldLock);

    assert.deepEqual(operations(html), ['downgrade', 'downgrade', 'downgrade', 'downgrade', 'remove']);
});

function operations(html) {
    return [...html.matchAll(/class="operation-(\w+)"/g)].map(([, operation]) => operation);
}
