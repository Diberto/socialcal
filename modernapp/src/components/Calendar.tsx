import { useState } from 'react';
import './Calendar.css';

function getMonthDays(date: Date) {
  const year = date.getFullYear();
  const month = date.getMonth();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const firstDay = new Date(year, month, 1).getDay();
  const days: (number | null)[] = [];
  for (let i = 0; i < firstDay; i++) days.push(null);
  for (let d = 1; d <= daysInMonth; d++) days.push(d);
  return days;
}

export default function Calendar() {
  const [current, setCurrent] = useState(() => new Date());

  const days = getMonthDays(current);
  const month = current.toLocaleString('default', { month: 'long' });
  const year = current.getFullYear();

  const prevMonth = () =>
    setCurrent(new Date(current.getFullYear(), current.getMonth() - 1, 1));
  const nextMonth = () =>
    setCurrent(new Date(current.getFullYear(), current.getMonth() + 1, 1));
  const goToday = () => setCurrent(new Date());

  return (
    <div className="calendar">
      <div className="header">
        <button onClick={prevMonth}>◀</button>
        <span className="title">{month} {year}</span>
        <button onClick={nextMonth}>▶</button>
        <button onClick={goToday} className="today">Hoy</button>
      </div>
      <div className="grid">
        {['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'].map(d => (
          <div key={d} className="dow">{d}</div>
        ))}
        {days.map((d, idx) => (
          <div key={idx} className="day">
            {d && <span className="num">{d}</span>}
          </div>
        ))}
      </div>
    </div>
  );
}
