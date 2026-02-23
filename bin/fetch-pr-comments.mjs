#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';

import { execSync } from 'node:child_process';

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
const pullNumber = Number.parseInt(env.PR_NUMBER, 10);

if (!token || !pullNumber || Number.isNaN(pullNumber)) {
	console.error('Ошибка: GITHUB_TOKEN или корректный PR_NUMBER не найдены в .env');
	process.exit(1);
}

let owner, repo;

try {
	const remoteUrl = execSync('git remote get-url origin', {
		encoding: 'utf8',
		env: process.env,
	}).trim();
	const repoMatch = remoteUrl.match(/github\.com[:/]([^/]+)\/([^/.]+?)(?:\.git)?$/);
	if (!repoMatch) throw new Error('Не удалось разобрать URL репозитория');
	owner = repoMatch[1];
	repo = repoMatch[2];
} catch (e) {
	console.error(`Ошибка: ${e.message}.`);
	process.exit(1);
}

const query = `
query($owner: String!, $repo: String!, $pullNumber: Int!, $cursor: String) {
  repository(owner: $owner, name: $repo) {
    pullRequest(number: $pullNumber) {
      reviewThreads(first: 100, after: $cursor) {
        pageInfo {
          hasNextPage
          endCursor
        }
        nodes {
          id
          isResolved
          path
          line
          comments(first: 1) {
            nodes {
              body
              url
            }
          }
        }
      }
    }
  }
}
`;

async function fetchGraphQL(query, variables) {
	const response = await fetch('https://api.github.com/graphql', {
		method: 'POST',
		headers: {
			'Authorization': `Bearer ${token}`,
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({ query, variables }),
	});

	const data = await response.json();
	if (!response.ok || (data.errors && data.errors.length > 0)) {
		const errorMsg = data.errors ? data.errors[0].message : response.statusText;
		throw new Error(`Ошибка GraphQL API: ${errorMsg}`);
	}

	return data.data;
}

function cleanBody(body) {
	if (!body.includes('CodeRabbit')) return body.trim();

	let mainPart = body.split(/<details|---|<!--/)[0].trim();
	mainPart = mainPart.replaceAll(/_⚠️ Potential issue_[\s|]*_[^_]*_\s*/g, '').trim();

	if (mainPart.length > 0) {
		const firstChar = mainPart[0];
		if (/^\p{Lu}$/u.test(firstChar) && mainPart.length > 1) {
			mainPart = firstChar.toLowerCase() + mainPart.slice(1);
		}
	}

	const summaryMarker = '<summary>🤖 Prompt for AI Agents</summary>';
	const summaryIndex = body.indexOf(summaryMarker);
	let cleanPrompt = '';
	if (summaryIndex !== -1) {
		const detailsIndex = body.indexOf('</details>', summaryIndex);
		const detailsChunk = detailsIndex === -1 ? body.slice(summaryIndex) : body.slice(summaryIndex, detailsIndex);
		const fenceStart = detailsChunk.indexOf('```');
		if (fenceStart !== -1) {
			const fenceEnd = detailsChunk.indexOf('```', fenceStart + 3);
			if (fenceEnd !== -1) {
				let promptText = detailsChunk.slice(fenceStart + 3, fenceEnd).trim();
				const firstNewline = promptText.indexOf('\n');
				if (firstNewline !== -1) {
					promptText = promptText.slice(firstNewline + 1).trim();
				}
				if (promptText !== '') {
					cleanPrompt = `\n\n> 🤖 **Prompt:**\n> ${promptText.replaceAll(/\n/g, '\n> ')}`;
				}
			}
		}
	}

	return `${mainPart}${cleanPrompt}`;
}

function parseExistingReview(filePath) {
	const entries = {};

	if (!fs.existsSync(filePath)) {
		return entries;
	}

	const content = fs.readFileSync(filePath, 'utf8');
	const sections = content.split(/(?=^### #\d+)/m);

	for (const section of sections) {
		const threadMatch = section.match(/<!-- threadId: ([^\s]+) -->/);
		if (!threadMatch) continue;

		const threadId = threadMatch[1];
		entries[threadId] = section.trimEnd();
	}

	return entries;
}

async function main() {
	try {
		const args = process.argv.slice(2);
		const includeResolved = args.includes('--include-resolved');

		console.log(`Получение ВСЕХ обсуждений для PR #${pullNumber} (${owner}/${repo})...`);

		let allThreads = [];
		let hasNextPage = true;
		let cursor = null;

		while (hasNextPage) {
			const result = await fetchGraphQL(query, { owner, repo, pullNumber, cursor });

			if (!result.repository?.pullRequest) {
				console.error(`Ошибка: PR #${pullNumber} не найден в репозитории ${owner}/${repo}`);
				process.exit(1);
			}

			const { nodes, pageInfo } = result.repository.pullRequest.reviewThreads;
			allThreads = allThreads.concat(nodes);

			hasNextPage = pageInfo.hasNextPage;
			cursor = pageInfo.endCursor;

			if (hasNextPage) {
				process.stdout.write('.');
			}
		}
		console.log(' Готово.');

		const threads = allThreads;
		const unresolvedThreads = threads.filter(thread => !thread.isResolved);
		const resolvedThreads = threads.filter(thread => thread.isResolved);

		console.log(`Всего тредов: ${threads.length}`);
		console.log(`Открытых: ${unresolvedThreads.length}`);
		console.log(`Разрешенных: ${resolvedThreads.length}`);

		const threadsToProcess = includeResolved ? threads : unresolvedThreads;

		const outputPath = path.resolve(process.cwd(), 'docs/REVIEW.md');
		fs.mkdirSync(path.dirname(outputPath), { recursive: true });

		const existingEntries = parseExistingReview(outputPath);
		const preservedCount = Object.keys(existingEntries).length;
		if (preservedCount > 0) {
			console.log(`Найдено ${preservedCount} существующих записей для сохранения.`);
		}

		if (threadsToProcess.length === 0) {
			console.log('Нет комментариев для обработки.');
			if (resolvedThreads.length > 0 && !includeResolved) {
				console.log('💡 Совет: Запустите с флагом `--include-resolved`, чтобы включить разрешенные комментарии.');
			}
			if (preservedCount > 0) {
				console.log(`⚠️  Файл ${outputPath} содержит ${preservedCount} записей с пользовательскими правками — перезапись пропущена.`);
			} else {
				const emptyMarkdown = `# Задачи по ревью PR - #${pullNumber}\n\n`;
				fs.writeFileSync(outputPath, emptyMarkdown + '✅ Все комментарии закрыты!\n');
			}
			return;
		}

		let markdown = `# Задачи по ревью PR - #${pullNumber}\n\n`;
		markdown += `**Источник:** [PR #${pullNumber} на GitHub](https://github.com/${owner}/${repo}/pull/${pullNumber})
`;
		markdown += `**Сгенерировано:** ${new Date().toLocaleString()}

`;
		markdown += `> [!NOTE]
`;
		markdown += `> Этот файл создан автоматически. Отмечайте выполненные пункты как [x].
> Скрипт \`make review-resolve\` закроет на GitHub все треды, у которых главный чекбокс \`[x]\`.

`;

		let itemNumber = 0;

		const threadsByFile = threadsToProcess.reduce((acc, thread) => {
			const file = thread.path || 'Общие замечания';
			if (!acc[file]) acc[file] = [];
			acc[file].push(thread);
			return acc;
		}, {});

		for (const [file, fileThreads] of Object.entries(threadsByFile)) {
			markdown += `## 📄 Файл: ${file}\n\n`;

			for (const thread of fileThreads) {
				const firstComment = thread.comments?.nodes?.[0];
				if (!firstComment) continue;

				itemNumber++;
				const threadId = thread.id;

				if (existingEntries[threadId]) {
					const existing = existingEntries[threadId];
					const status = thread.isResolved ? '✅ (RESOLVED)' : '⭕ (OPEN)';
					const updatedEntry = existing
						.replace(/^### #\d+/, `### #${itemNumber}`)
						.replace(/[⭕✅] \((?:OPEN|RESOLVED)\)/, status);
					markdown += updatedEntry + '\n\n';
				} else {
					const rawBody = firstComment.body;
					const body = cleanBody(rawBody);
					const line = thread.line || 'diff';
					const url = firstComment.url;
					const status = thread.isResolved ? '✅ (RESOLVED)' : '⭕ (OPEN)';

					markdown += `### #${itemNumber} 💬 Комментарий на строке ${line} ${status}\n`;
					markdown += `<!-- threadId: ${threadId} -->\n`;
					markdown += `- [ ] **Задача:** ${body}\n`;
					markdown += `  - **Перевод:** [ждет вашего описания]\n`;
					markdown += `  - **Оценка сложности (1-10):** [ ]\n`;
					markdown += `  - **Стоит ли исправлять:** [ ] да / [ ] нет / [ ] обсудить\n`;
					markdown += `  - [Посмотреть на GitHub](${url})\n\n`;
				}
			}
		}

		fs.writeFileSync(outputPath, markdown.replaceAll(/—/g, '-'));

		console.log(`\nГотово! Создан чеклист для ${threadsToProcess.length} веток обсуждения.`);
		console.log(`Файл: ${outputPath}`);

	} catch (error) {
		console.error(`\nОшибка при выполнении: ${error.message}`);
		process.exit(1);
	}
}

const NODE_MAJOR = Number.parseInt(process.versions.node.split('.')[0], 10);
if (NODE_MAJOR < 18) {
	console.error('❌ Ошибка: Требуется Node.js версии 18 или выше для работы глобального fetch().');
	process.exit(1);
}

main();
