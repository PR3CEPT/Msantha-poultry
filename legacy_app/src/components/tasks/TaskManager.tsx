import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { FarmTask, TaskCategory } from '../../types';
import { formatDate } from '../../utils/i18n';
import { CheckSquare, Plus, CheckCircle2, Clock, Calendar, AlertCircle } from 'lucide-react';

export const TaskManager: React.FC = () => {
  const { db, currentUser, addTask, updateTaskStatus } = useFarmData();

  const [activeFilter, setActiveFilter] = useState<'all' | 'today' | 'upcoming' | 'overdue' | 'completed'>('today');
  const [showAddModal, setShowAddModal] = useState(false);

  // Form State
  const today = new Date().toISOString().split('T')[0];
  const [taskTitle, setTaskTitle] = useState('');
  const [taskCategory, setTaskCategory] = useState<TaskCategory>('egg_collection');
  const [taskDueDate, setTaskDueDate] = useState(today);
  const [taskDueTime, setTaskDueTime] = useState('08:00');
  const [taskAssigned, setTaskAssigned] = useState(currentUser.name);
  const [taskPriority, setTaskPriority] = useState<FarmTask['priority']>('medium');
  const [taskNotes, setTaskNotes] = useState('');

  const handleCreateTask = (e: React.FormEvent) => {
    e.preventDefault();
    if (!taskTitle) return;

    addTask({
      title: taskTitle,
      category: taskCategory,
      dueDate: taskDueDate,
      dueTime: taskDueTime,
      assignedPerson: taskAssigned,
      priority: taskPriority,
      status: 'pending',
      notes: taskNotes,
    });

    setShowAddModal(false);
    setTaskTitle('');
    setTaskNotes('');
  };

  const filteredTasks = db.tasks.filter((t) => {
    if (activeFilter === 'today') return t.dueDate === today && t.status !== 'completed';
    if (activeFilter === 'upcoming') return t.dueDate > today && t.status !== 'completed';
    if (activeFilter === 'overdue') return t.dueDate < today && t.status !== 'completed';
    if (activeFilter === 'completed') return t.status === 'completed';
    return true;
  });

  const todayCount = db.tasks.filter((t) => t.dueDate === today && t.status !== 'completed').length;
  const overdueCount = db.tasks.filter((t) => t.dueDate < today && t.status !== 'completed').length;
  const upcomingCount = db.tasks.filter((t) => t.dueDate > today && t.status !== 'completed').length;
  const completedCount = db.tasks.filter((t) => t.status === 'completed').length;

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">Farm Tasks & Daily Routine</h2>
          <p className="text-xs text-slate-500">
            Egg collections, incubator transfers, brooder cleaning, vaccinations, and customer pick-up reminders.
          </p>
        </div>

        <button
          onClick={() => setShowAddModal(true)}
          className="flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
        >
          <Plus className="w-4 h-4" />
          <span>Schedule Task</span>
        </button>
      </div>

      {/* Filter Tabs */}
      <div className="bg-white p-1 rounded-xl border border-slate-200 flex flex-wrap text-xs font-semibold">
        <button
          onClick={() => setActiveFilter('today')}
          className={`flex-1 min-w-[100px] py-2 rounded-lg transition ${
            activeFilter === 'today' ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          Due Today ({todayCount})
        </button>
        <button
          onClick={() => setActiveFilter('overdue')}
          className={`flex-1 min-w-[100px] py-2 rounded-lg transition ${
            activeFilter === 'overdue' ? 'bg-rose-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          Overdue ({overdueCount})
        </button>
        <button
          onClick={() => setActiveFilter('upcoming')}
          className={`flex-1 min-w-[100px] py-2 rounded-lg transition ${
            activeFilter === 'upcoming' ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          Upcoming ({upcomingCount})
        </button>
        <button
          onClick={() => setActiveFilter('completed')}
          className={`flex-1 min-w-[100px] py-2 rounded-lg transition ${
            activeFilter === 'completed' ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          Completed ({completedCount})
        </button>
        <button
          onClick={() => setActiveFilter('all')}
          className={`flex-1 min-w-[100px] py-2 rounded-lg transition ${
            activeFilter === 'all' ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          All Tasks ({db.tasks.length})
        </button>
      </div>

      {/* Task List */}
      <div className="space-y-2.5">
        {filteredTasks.length === 0 ? (
          <div className="bg-white rounded-xl border border-slate-200 p-8 text-center text-xs text-slate-400 italic">
            No tasks found in this view.
          </div>
        ) : (
          filteredTasks.map((t) => {
            const isDone = t.status === 'completed';
            const isOverdue = t.dueDate < today && !isDone;

            return (
              <div
                key={t.id}
                className={`bg-white rounded-xl border p-4 shadow-xs flex items-start justify-between gap-3 transition hover:border-slate-300 ${
                  isDone
                    ? 'border-slate-200 opacity-70'
                    : isOverdue
                    ? 'border-rose-300 ring-2 ring-rose-100'
                    : 'border-slate-200'
                }`}
              >
                <div className="flex items-start gap-3 min-w-0">
                  <button
                    onClick={() => updateTaskStatus(t.id, isDone ? 'pending' : 'completed')}
                    className={`mt-0.5 rounded-lg p-1 transition ${
                      isDone
                        ? 'text-emerald-700 bg-emerald-50'
                        : 'text-slate-400 hover:text-emerald-700 hover:bg-slate-50'
                    }`}
                  >
                    <CheckCircle2 className={`w-5 h-5 ${isDone ? 'fill-emerald-700 text-white' : ''}`} />
                  </button>

                  <div className="min-w-0">
                    <h3 className={`text-sm font-bold ${isDone ? 'line-through text-slate-400' : 'text-slate-900'}`}>
                      {t.title}
                    </h3>
                    <div className="flex items-center gap-2 mt-1 text-xs text-slate-500 flex-wrap">
                      <span className="capitalize font-medium text-slate-700">
                        {t.category.replace('_', ' ')}
                      </span>
                      <span aria-hidden="true">·</span>
                      <span className="flex items-center gap-1">
                        <Calendar className="w-3 h-3 text-slate-400" />
                        {formatDate(t.dueDate)} {t.dueTime && `@ ${t.dueTime}`}
                      </span>
                      <span aria-hidden="true">·</span>
                      <span>Assigned to: <strong>{t.assignedPerson}</strong></span>
                    </div>

                    {t.notes && <p className="text-xs text-slate-500 italic mt-1.5">{t.notes}</p>}
                  </div>
                </div>

                <div className="flex flex-col items-end gap-1 shrink-0">
                  <span
                    className={`text-[10px] font-bold px-2 py-0.5 rounded uppercase ${
                      t.priority === 'high'
                        ? 'bg-rose-100 text-rose-800'
                        : t.priority === 'medium'
                        ? 'bg-amber-100 text-amber-800'
                        : 'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {t.priority}
                  </span>

                  {isOverdue && (
                    <span className="text-[10px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded">
                      Overdue
                    </span>
                  )}
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* Schedule Task Modal */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Schedule Farm Task</h3>
            <p className="text-xs text-slate-500 mb-4">Assign tasks to staff members with deadlines.</p>

            <form onSubmit={handleCreateTask} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Task Title</label>
                <input
                  type="text"
                  placeholder="e.g. Inspect temperature on Incubator 1, Turn on fans"
                  value={taskTitle}
                  onChange={(e) => setTaskTitle(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Category</label>
                  <select
                    value={taskCategory}
                    onChange={(e) => setTaskCategory(e.target.value as TaskCategory)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="egg_collection">Egg Collection</option>
                    <option value="feeding">Feeding</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="disinfection">Disinfection</option>
                    <option value="vaccination">Vaccination</option>
                    <option value="medication">Medication</option>
                    <option value="candling">Candling</option>
                    <option value="incubator_transfer">Lockdown / Transfer</option>
                    <option value="hatch_monitoring">Hatch Monitoring</option>
                    <option value="customer_collection">Customer Collection</option>
                    <option value="stock_check">Stock Count</option>
                    <option value="equipment_maintenance">Equipment / Solar</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Priority</label>
                  <select
                    value={taskPriority}
                    onChange={(e) => setTaskPriority(e.target.value as any)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Due Date</label>
                  <input
                    type="date"
                    value={taskDueDate}
                    onChange={(e) => setTaskDueDate(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Due Time</label>
                  <input
                    type="time"
                    value={taskDueTime}
                    onChange={(e) => setTaskDueTime(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Assign To</label>
                <input
                  type="text"
                  value={taskAssigned}
                  onChange={(e) => setTaskAssigned(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Notes</label>
                <input
                  type="text"
                  placeholder="Additional instructions..."
                  value={taskNotes}
                  onChange={(e) => setTaskNotes(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Save Task
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
