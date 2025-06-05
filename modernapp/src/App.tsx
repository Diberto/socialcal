import { useState } from 'react';
import './App.css';

/**
 * Minimal React version of the Social Calendar.
 * This is a starting point and does not yet implement
 * the full functionality from index.html.
 */
export default function App() {
  const [message] = useState('Social Calendar');
  return (
    <div className="p-4">
      <h1 className="text-2xl font-bold mb-4">{message}</h1>
      <p>TODO: Port calendar features to React/TypeScript.</p>
    </div>
  );
}
