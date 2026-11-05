import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import {
  Bell,
  AlertTriangle,
  AlertCircle,
  Flame,
  Wheat,
  Egg,
  CheckSquare,
  Activity,
  DollarSign,
  X,
  CheckCircle2,
  ChevronRight,
  Volume2,
  RefreshCw,
} from 'lucide-react';
import { FarmAlert } from '../../types';

interface AlertCenterModalProps {
  isOpen: boolean;
  onClose: () => void;
  onNavigateTab?: (tab: string) => void;
}

export const AlertCenterModal: React.FC<AlertCenterModalProps> = ({
  isOpen,
  onClose,
  onNavigateTab,
}) => {
  const { allAlerts, activeAlerts, dismissAlert, clearAllAlertDismissals } = useFarmData();
  const [filter, setFilter] = useState<'all' | 'critical' | 'incubation' | 'feed'>('all');

  if (!isOpen) return null;

  const filteredAlerts = activeAlerts.filter((a) => {
    if (filter === 'critical') return a.severity === 'critical';
    if (filter === 'incubation') return a.category === 'incubation';
    if (filter === 'feed') return a.category === 'feed';
    return true;
  });

  const getAlertIcon = (category: FarmAlert['category']) => {
    switch (category) {
      case 'incubation':
        return <Flame className="w-4 h-4 text-amber-500" />;
      case 'feed':
        return <Wheat className="w-4 h-4 text-emerald-600" />;
      case 'stock':
        return <Egg className="w-4 h-4 text-amber-600" />;
      case 'tasks':
        return <CheckSquare className="w-4 h-4 text-indigo-500" />;
      case 'health':
        return <Activity className="w-4 h-4 text-rose-500" />;
      case 'finance':
        return <DollarSign className="w-4 h-4 text-emerald-700" />;
      default:
        return <AlertTriangle className="w-4 h-4 text-amber-500" />;
    }
  };

  const playNotificationChime = () => {
    try {
      const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
      osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1); // A5
      gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.35);
    } catch {
      // Audio not permitted or supported
    }
  };

  const handleActionClick = (alert: FarmAlert) => {
    if (alert.actionTab && onNavigateTab) {
      onNavigateTab(alert.actionTab);
    }
    dismissAlert(alert.id);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
      <div className="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in zoom-in-95 duration-200">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-rose-950 via-slate-900 to-emerald-950 text-white p-4 sm:p-5 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-rose-600/30 border border-rose-500/40 flex items-center justify-center shrink-0">
              <Bell className="w-5 h-5 text-amber-300 animate-bounce" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white uppercase tracking-wider">
                  Important Alert Center
                </span>
                <span className="text-xs text-rose-200 font-medium">
                  {activeAlerts.length} Active Notice{activeAlerts.length === 1 ? '' : 's'}
                </span>
              </div>
              <h2 className="text-base font-bold text-white mt-0.5">
                Msantha Poultry Farm Notifications
              </h2>
            </div>
          </div>

          <div className="flex items-center gap-1.5">
            <button
              onClick={playNotificationChime}
              title="Test Alert Chime"
              className="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition"
            >
              <Volume2 className="w-4 h-4" />
            </button>
            <button
              onClick={onClose}
              className="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Filter Pills */}
        <div className="flex border-b border-slate-200 bg-slate-50 px-4 py-2 gap-1.5 text-xs font-semibold overflow-x-auto">
          <button
            onClick={() => setFilter('all')}
            className={`px-3 py-1 rounded-lg transition ${
              filter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200'
            }`}
          >
            All Alerts ({activeAlerts.length})
          </button>
          <button
            onClick={() => setFilter('critical')}
            className={`px-3 py-1 rounded-lg transition ${
              filter === 'critical' ? 'bg-rose-700 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200'
            }`}
          >
            Critical Only ({activeAlerts.filter((a) => a.severity === 'critical').length})
          </button>
          <button
            onClick={() => setFilter('incubation')}
            className={`px-3 py-1 rounded-lg transition ${
              filter === 'incubation' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200'
            }`}
          >
            Incubation ({activeAlerts.filter((a) => a.category === 'incubation').length})
          </button>
          <button
            onClick={() => setFilter('feed')}
            className={`px-3 py-1 rounded-lg transition ${
              filter === 'feed' ? 'bg-emerald-700 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200'
            }`}
          >
            Feed ({activeAlerts.filter((a) => a.category === 'feed').length})
          </button>
        </div>

        {/* Alerts List */}
        <div className="p-4 space-y-3 max-h-[60vh] overflow-y-auto">
          {filteredAlerts.length === 0 ? (
            <div className="text-center py-8 text-slate-500 space-y-2">
              <CheckCircle2 className="w-10 h-10 text-emerald-500 mx-auto" />
              <p className="font-bold text-sm text-slate-800">All Clear! No Active Alerts</p>
              <p className="text-xs text-slate-400">
                Incubators, feed stocks, health, and daily operations are running smoothly.
              </p>
              {allAlerts.length > 0 && (
                <button
                  onClick={clearAllAlertDismissals}
                  className="mt-2 text-xs text-emerald-700 hover:underline inline-flex items-center gap-1 font-semibold"
                >
                  <RefreshCw className="w-3 h-3" />
                  <span>Restore {allAlerts.length} previously dismissed alerts</span>
                </button>
              )}
            </div>
          ) : (
            filteredAlerts.map((alert) => {
              const isCrit = alert.severity === 'critical';
              return (
                <div
                  key={alert.id}
                  className={`p-3.5 rounded-xl border transition shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 ${
                    isCrit
                      ? 'bg-rose-50/70 border-rose-200 text-rose-950'
                      : 'bg-amber-50/60 border-amber-200 text-amber-950'
                  }`}
                >
                  <div className="flex items-start gap-3 min-w-0">
                    <div
                      className={`p-2 rounded-lg shrink-0 mt-0.5 ${
                        isCrit ? 'bg-rose-100' : 'bg-amber-100'
                      }`}
                    >
                      {getAlertIcon(alert.category)}
                    </div>
                    <div className="min-w-0">
                      <div className="flex items-center gap-1.5 flex-wrap">
                        <span
                          className={`text-[9px] px-1.5 py-0.2 rounded-md font-black uppercase tracking-wider ${
                            isCrit
                              ? 'bg-rose-600 text-white'
                              : 'bg-amber-600 text-white'
                          }`}
                        >
                          {alert.severity}
                        </span>
                        <h4 className="text-xs font-bold truncate">{alert.title}</h4>
                      </div>
                      <p className="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                        {alert.description}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-1.5 w-full sm:w-auto justify-end shrink-0 pt-1 sm:pt-0 border-t sm:border-0 border-slate-200/60">
                    {alert.actionLabel && (
                      <button
                        onClick={() => handleActionClick(alert)}
                        className="px-3 py-1.5 rounded-lg bg-emerald-800 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-xs transition"
                      >
                        <span>{alert.actionLabel}</span>
                        <ChevronRight className="w-3.5 h-3.5" />
                      </button>
                    )}
                    <button
                      onClick={() => dismissAlert(alert.id)}
                      className="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-600 text-xs font-semibold border border-slate-200 transition"
                      title="Acknowledge alert"
                    >
                      Dismiss
                    </button>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Footer */}
        <div className="bg-slate-50 px-4 py-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
          <span>Priority Alert System • Msantha Farm Liwonde</span>
          <button
            onClick={onClose}
            className="px-4 py-1.5 rounded-lg bg-slate-900 text-white font-semibold text-xs hover:bg-slate-800 transition"
          >
            Close Alert Center
          </button>
        </div>

      </div>
    </div>
  );
};
