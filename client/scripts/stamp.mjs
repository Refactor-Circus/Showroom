// Stamps src/schema.d.ts with the sha256 of the openapi.json it was generated
// from, then ships it as dist/schema.d.ts (tsc emits nothing for a .d.ts).
// tests/OpenApi/OpenApiTest.php compares the stamp with the committed
// openapi.json, so a stale client fails `composer test` without Node.

import { createHash } from 'node:crypto';
import { copyFileSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const client = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const openapi = resolve(client, '..', 'openapi.json');
const source = resolve(client, 'src', 'schema.d.ts');
const built = resolve(client, 'dist', 'schema.d.ts');

const prefix = '// openapi.json sha256: ';
const hash = createHash('sha256').update(readFileSync(openapi)).digest('hex');

const lines = readFileSync(source, 'utf8').replace(/\r\n/g, '\n').split('\n');

if (lines[0].startsWith(prefix)) {
  lines.shift();
}

writeFileSync(source, [prefix + hash, ...lines].join('\n'));

mkdirSync(dirname(built), { recursive: true });
copyFileSync(source, built);
