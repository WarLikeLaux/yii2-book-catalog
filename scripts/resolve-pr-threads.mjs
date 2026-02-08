#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';

const envPath = path.resolve(process.cwd(), '.env');
const env = { ...process.env };
if (fs.existsSync(envPath)) {
  fs.readFileSync(envPath, "utf8")
    .split("\n")
    .filter(line => line.trim() && !line.startsWith("#"))
    .forEach((line) => {
      const [key, ...valueParts] = line.split("=");
      if (key && valueParts.length > 0) {
        let value = valueParts.join("=").trim();
        if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
          value = value.slice(1, -1);
        }
        env[key.trim()] = value;
      }
    });
}

const token = env.GITHUB_TOKEN;

if (!token) {
	console.error('Ошибка: GITHUB_TOKEN не найден в .env');
	process.exit(1);
}

const resolveMutation = `
mutation($threadId: ID!) {
  resolveReviewThread(input: { threadId: $threadId }) {
    thread {
      id
      isResolved
    }
  }
}
`;

async function resolveThread(threadId) {
	const response = await fetch('https://api.github.com/graphql', {
		method: 'POST',
		headers: {
			'Authorization': `Bearer ${token}`,
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({ query: resolveMutation, variables: { threadId } }),
	});

	const data = await response.json();
	if (data.errors && data.errors.length > 0) {
		return { isResolved: false, message: data.errors[0].message || "GraphQL error" };
	}
	if (!data.data?.resolveReviewThread?.thread) {
		return { isResolved: false, message: "Failed to resolve thread: missing data in response" };
	}
	return { isResolved: true, thread: data.data.resolveReviewThread.thread };
}

async function main() {
	const reviewPath = path.resolve(process.cwd(), 'docs/REVIEW.md');

	if (!fs.existsSync(reviewPath)) {
		console.error('Ошибка: docs/REVIEW.md не найден. Сначала запустите pnpm review:fetch');
		process.exit(1);
	}

	const content = fs.readFileSync(reviewPath, 'utf8');

	const threadPattern = /<!-- threadId: ([^\s]+) -->\r?\n- \[x\] \*\*Задача:\*\*/g;
	const matches = [...content.matchAll(threadPattern)];

	if (matches.length === 0) {
		console.log('Нет выполненных задач для закрытия.');
		console.log('Отметьте выполненные задачи как [x] и запустите снова.');
		return;
	}

	console.log(`Найдено ${matches.length} выполненных задач для закрытия на GitHub...\n`);

	const { createInterface } = await import('node:readline');
	const rl = createInterface({
		input: process.stdin,
		output: process.stdout,
	});

	const answer = await new Promise(resolve => {
		rl.question(`Вы уверены, что хотите закрыть ${matches.length} тредов? [y/N] `, resolve);
	});

	rl.close();

	if (!answer.trim().toLowerCase().startsWith('y')) {
		console.log('Отмена.');
		return;
	}

	console.log('\nЗакрытие тредов...');

	let resolved = 0;
	let failed = 0;

	for (const match of matches) {
		const threadId = match[1];
		try {
			const result = await resolveThread(threadId);
			if (result.isResolved) {
				console.log(`✅ Тред ${threadId} закрыт.`);
				resolved++;
			} else {
				console.error(`❌ Ошибка закрытия треда ${threadId}: ${result.message}`);
				failed++;
			}
		} catch (error) {
			console.error(`❌ Ошибка для ${threadId}: ${error.message}`);
			failed++;
		}
	}

	console.log(`\nГотово! Закрыто: ${resolved}, ошибок: ${failed}`);
}

const NODE_MAJOR = Number.parseInt(process.versions.node.split('.')[0], 10);
if (NODE_MAJOR < 18) {
	console.error('❌ Ошибка: Требуется Node.js версии 18 или выше для работы глобального fetch().');
	process.exit(1);
}

main();
