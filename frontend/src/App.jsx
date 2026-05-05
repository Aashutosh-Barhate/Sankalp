import { useEffect, useState } from 'react';
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';
const MODES = {
  focus: { label: 'Focus', seconds: 25 * 60, minutes: 25 },
  break: { label: 'Break', seconds: 5 * 60, minutes: 5 }
};

function formatTime(seconds) {
  const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
  const secs = (seconds % 60).toString().padStart(2, '0');
  return `${mins}:${secs}`;
}

function getApiError(error) {
  return error.response?.data?.error || 'Could not reach the API. Make sure the PHP backend is running.';
}

export default function App() {
  const [mode, setMode] = useState('focus');
  const [secondsLeft, setSecondsLeft] = useState(MODES.focus.seconds);
  const [isRunning, setIsRunning] = useState(false);
  const [taskInput, setTaskInput] = useState('');
  const [activeTask, setActiveTask] = useState('');
  const [stats, setStats] = useState({ totalSessions: 0, totalFocusTime: 0 });
  const [message, setMessage] = useState('');

  useEffect(() => {
    fetchStats();
  }, []);

  useEffect(() => {
    if (!isRunning) return;

    const intervalId = window.setInterval(() => {
      setSecondsLeft((current) => {
        if (current <= 1) {
          window.clearInterval(intervalId);
          completeSession();
          return 0;
        }

        return current - 1;
      });
    }, 1000);

    return () => window.clearInterval(intervalId);
  }, [isRunning, mode, activeTask]);

  async function fetchStats() {
    try {
      const response = await axios.get(`${API_URL}/stats`);
      setStats(response.data);
    } catch (error) {
      setMessage(getApiError(error));
    }
  }

  async function addTask(event) {
    event.preventDefault();
    const text = taskInput.trim();
    if (!text) return;

    try {
      const response = await axios.post(`${API_URL}/task`, { text });
      setActiveTask(response.data.task.text);
      setTaskInput('');
      setMessage('Task added.');
    } catch (error) {
      setMessage(getApiError(error));
    }
  }

  async function startTimer() {
    if (!activeTask) {
      setMessage('Add a task before starting.');
      return;
    }

    try {
      await axios.post(`${API_URL}/start-session`, { task: activeTask, mode });
      setIsRunning(true);
      setMessage('Session started.');
    } catch (error) {
      setMessage(getApiError(error));
    }
  }

  function pauseTimer() {
    setIsRunning(false);
    setMessage('Timer paused.');
  }

  function resetTimer() {
    setIsRunning(false);
    setSecondsLeft(MODES[mode].seconds);
    setMessage('Timer reset.');
  }

  async function completeSession() {
    setIsRunning(false);

    try {
      await axios.post(`${API_URL}/complete-session`, {
        task: activeTask,
        mode,
        duration: MODES[mode].minutes
      });
      await fetchStats();
      setMessage(`${MODES[mode].label} session completed.`);
    } catch (error) {
      setMessage(getApiError(error));
    }
  }

  function changeMode(nextMode) {
    setMode(nextMode);
    setSecondsLeft(MODES[nextMode].seconds);
    setIsRunning(false);
    setMessage('');
  }

  return (
    <main className="app-shell">
      <section className="dashboard">
        <p className="eyebrow">YPT</p>
        <h1>Your Productivity Timer</h1>

        <div className="mode-toggle" aria-label="Timer mode">
          {Object.entries(MODES).map(([key, value]) => (
            <button
              key={key}
              className={mode === key ? 'active' : ''}
              onClick={() => changeMode(key)}
              type="button"
            >
              {value.label}
            </button>
          ))}
        </div>

        <div className="timer-card">
          <span className="mode-label">{MODES[mode].label}</span>
          <div className="time">{formatTime(secondsLeft)}</div>
          <div className="actions">
            <button onClick={startTimer} disabled={isRunning} type="button">Start</button>
            <button onClick={pauseTimer} disabled={!isRunning} type="button">Pause</button>
            <button onClick={resetTimer} type="button">Reset</button>
          </div>
        </div>

        <form className="task-form" onSubmit={addTask}>
          <input
            value={taskInput}
            onChange={(event) => setTaskInput(event.target.value)}
            placeholder="What are you working on?"
            aria-label="Task"
          />
          <button type="submit">Add Task</button>
        </form>

        <div className="active-task">
          <span>Active task</span>
          <strong>{activeTask || 'No task selected'}</strong>
        </div>

        {message && <p className="message">{message}</p>}

        <div className="stats">
          <div>
            <span>Total sessions</span>
            <strong>{stats.totalSessions}</strong>
          </div>
          <div>
            <span>Focus time</span>
            <strong>{stats.totalFocusTime} min</strong>
          </div>
        </div>
      </section>
    </main>
  );
}
