# YPT (Your Productivity Timer)

Minimal full-stack productivity timer with a React frontend and PHP REST API backend.

## Run Backend

```bash
php -S 127.0.0.1:8000 -t backend/public
```

## Run Frontend

```bash
cd frontend
npm install
npm run dev
```

The frontend expects the API at `http://127.0.0.1:8000`. You can override it with `VITE_API_URL`.

## API

- `POST /task` with `{ "text": "Write report" }`
- `POST /start-session` with `{ "task": "Write report", "mode": "focus" }`
- `POST /complete-session` with `{ "task": "Write report", "mode": "focus", "duration": 25 }`
- `GET /stats`
