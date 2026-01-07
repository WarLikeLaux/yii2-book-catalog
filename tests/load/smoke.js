import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 20,
  duration: '10s',
};

export default function () {
  const BASE_URL = 'http://php'; 

  const res = http.get(BASE_URL);
  check(res, {
    'front status is 200': (r) => r.status === 200,
    'main page content': (r) => r.body.includes('Book Catalog'),
  });

  const params = {
    headers: {
      'Accept': 'application/json',
    },
  };
  const resApi = http.get(`${BASE_URL}/api/v1/books`, params);
  
  if (resApi.status !== 200) {
      console.log(`API Error: ${resApi.status} ${resApi.body}`);
  }

  check(resApi, {
    'api status is 200': (r) => r.status === 200,
  });
}