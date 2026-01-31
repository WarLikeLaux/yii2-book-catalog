#!/usr/bin/env node

import fs from 'fs';
import path from 'path';

const envPath = path.resolve(process.cwd(), '.env');
const env = fs.existsSync(envPath)
	? Object.fromEntries(fs.readFileSync(envPath, 'utf8').split('\n').filter(Boolean).map(line => {
		const parts = line.split('=');
		return [parts[0].trim(), parts.slice(1).join('=').trim()];
	}))
	: process.env;

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
	if (!response.ok || data.errors) {
		const errorMsg = data.errors ? data.errors[0].message : response.statusText;
		throw new Error(`Ошибка при закрытии треда: ${errorMsg}`);
	}

	return data.data.resolveReviewThread.thread;
}

async function main() {
	const reviewPath = path.resolve(process.cwd(), 'docs/REVIEW.md');

	if (!fs.existsSync(reviewPath)) {
		console.error('Ошибка: docs/REVIEW.md не найден. Сначала запустите pnpm review:fetch');
		process.exit(1);
	}

	const content = fs.readFileSync(reviewPath, 'utf8');

	// Находим все выполненные задачи с threadId
	// Паттерн: <!-- threadId: ID --> после которого идёт - [x] **Задача:**
	const threadPattern = /<!-- threadId: ([^\s]+) -->\r?\n- \[x\] \*\*Задача:\*\*/g;
	const matches = [...content.matchAll(threadPattern)];

	if (matches.length === 0) {
		console.log('Нет выполненных задач для закрытия.');
		console.log('Отметьте выполненные задачи как [x] и запустите снова.');
		return;
	}

	console.log(`Найдено ${matches.length} выполненных задач для закрытия на GitHub...\n`);

	const { createInterface } = await import('readline');
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
				console.log(`✅ Закрыт тред: ${threadId}`);
				resolved++;
			}
		} catch (error) {
			console.error(`❌ Ошибка для ${threadId}: ${error.message}`);
			failed++;
		}
	}

	console.log(`\nГотово! Закрыто: ${resolved}, ошибок: ${failed}`);
}

main();
