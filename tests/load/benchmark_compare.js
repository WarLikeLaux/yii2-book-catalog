import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    franken_php: {
      executor: 'constant-vus',
      vus: 20,
      duration: '10s',
      startTime: '0s',
      exec: 'franken',
      tags: { stack: 'franken' },
    },
    legacy_fpm: {
      executor: 'constant-vus',
      vus: 20,
      duration: '10s',
      startTime: '15s',
      exec: 'legacy',
      tags: { stack: 'legacy' },
    },
  },
  thresholds: {
    'http_req_duration{stack:franken}': ['p(95)<200'],
    'http_req_duration{stack:legacy}': ['p(95)<200'],
  },
};

export function franken() {
  testImpl('http://php');
}

export function legacy() {
  testImpl('http://legacy-app');
}

function testImpl(baseUrl) {
  const res = http.get(baseUrl);
  
  if (res.status !== 200) {
  }

  check(res, {
    'status is 200': (r) => r.status === 200,
    'content ok': (r) => r.body && r.body.includes('Book Catalog'),
  });
}
