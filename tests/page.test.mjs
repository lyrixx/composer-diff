import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';

// It runs public/build/php-web.mjs: run "castor app:build" first
import { compare } from '../public/page.mjs';

const oldLock = await readFile(new URL('../data/old.json', import.meta.url), 'utf8');
const newLock = await readFile(new URL('../data/new.json', import.meta.url), 'utf8');
const markdownSection = /Comparison Results in markdown/;
const sameLocksAlert = /looks the same/;

test('the page compares the examples', async () => {
    const html = await compare(oldLock, newLock);

    assert.deepEqual(operations(html), ['upgrade', 'upgrade', 'install', 'upgrade', 'upgrade']);
    assert.match(html, markdownSection);
    assert.doesNotMatch(html, /^(Deprecated|Notice|Warning): /m);
});

// The page runs PHP again on each submit
test('the page compares them again, the other way around', async () => {
    const html = await compare(newLock, oldLock);

    assert.deepEqual(operations(html), ['downgrade', 'downgrade', 'downgrade', 'downgrade', 'remove']);
});

test('the page hides the table of the dev packages when none changed', async () => {
    const html = await compare(oldLock, newLock);

    assert.deepEqual(tables(html), ['Packages']);
    assert.doesNotMatch(html, sameLocksAlert);
});

test('the page hides the table of the packages when only the dev ones changed', async () => {
    const html = await compare(asDev(oldLock), asDev(newLock));

    assert.deepEqual(tables(html), ['Dev Packages']);
    assert.deepEqual(operations(html), ['upgrade', 'upgrade', 'install', 'upgrade', 'upgrade']);
    assert.doesNotMatch(html, sameLocksAlert);
});

test('the page says when both files are the same', async () => {
    const html = await compare(oldLock, oldLock);

    assert.deepEqual(tables(html), []);
    assert.match(html, sameLocksAlert);
    assert.doesNotMatch(html, markdownSection);
});

test('the page gives each table its icon', async () => {
    assert.deepEqual(icons(await compare(oldLock, newLock)), ['fa-box']);
    assert.deepEqual(icons(await compare(asDev(oldLock), asDev(newLock))), ['fa-wrench']);
});

function operations(html) {
    return [...html.matchAll(/class="operation-(\w+)"/g)].map(([, operation]) => operation);
}

function tables(html) {
    return [...html.matchAll(/<h3>.*?<\/i>\s*(.+?)\s*<\/h3>/gs)].map(([, title]) => title);
}

function icons(html) {
    return [...html.matchAll(/<h3>\s*<i class="fas (fa-[\w-]+)/g)].map(([, icon]) => icon);
}

function asDev(lock) {
    const { packages, ...rest } = JSON.parse(lock);

    return JSON.stringify({ ...rest, packages: [], 'packages-dev': packages });
}
