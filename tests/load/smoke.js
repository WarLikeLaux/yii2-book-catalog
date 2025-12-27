import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 10,
  duration: '10s',
};

export default function () {
  const BASE_URL = 'http://php'; 

  const res = http.get(BASE_URL);
  check(res, {
    'status is 200': (r) => r.status === 200,
  });

  const resApi = http.get(`${BASE_URL}/api/books`);
  check(resApi, {
    'api status is 200': (r) => r.status === 200,
  });

  sleep(1);
}