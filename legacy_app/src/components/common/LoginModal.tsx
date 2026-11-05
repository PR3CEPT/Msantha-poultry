import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { Shield, Lock, Check, KeyRound, User, AlertCircle, Eye, EyeOff } from 'lucide-react';
import { UserProfile } from '../../types';

interface LoginModalProps {
  isOpen: boolean;
  onClose?: () => void;
  isForced?: boolean; // if logged out completely
}

export const LoginModal: React.FC<LoginModalProps> = ({ isOpen, onClose, isForced = false }) => {
  const { users, currentUser, login } = useFarmData();
  const [selectedUser, setSelectedUser] = useState<UserProfile>(currentUser);
  const [pin, setPin] = useState('');
  const [showPin, setShowPin] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  if (!isOpen) return null;

  const handleSelectUser = (user: UserProfile) => {
    setSelectedUser(user);
    setPin('');
    setErrorMsg(null);
  };

  const handleKeypadPress = (digit: string) => {
    if (pin.length < 6) {
      setPin((prev) => prev + digit);
    }
  };

  const handleBackspace = () => {
    setPin((prev) => prev.slice(0, -1));
  };

  const handleSubmit = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (!pin) {
      setErrorMsg('Please enter your 4-digit PIN code.');
      return;
    }

    const res = login(selectedUser.id, pin);
    if (res.success) {
      setSuccessMsg(res.message);
      setTimeout(() => {
        setSuccessMsg(null);
        setPin('');
        if (onClose) onClose();
      }, 600);
    } else {
      setErrorMsg(res.message);
    }
  };

  const handleQuickDemoLogin = (user: UserProfile) => {
    setSelectedUser(user);
    const res = login(user.id, user.pin || '1234');
    if (res.success) {
      setSuccessMsg(`Authenticated as ${user.name}`);
      setTimeout(() => {
        setSuccessMsg(null);
        if (onClose) onClose();
      }, 500);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
      <div className="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in zoom-in-95 duration-200">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-emerald-950 via-emerald-900 to-slate-900 text-white p-5 text-center">
          <div className="w-12 h-12 rounded-2xl bg-emerald-800/80 border border-emerald-600/40 flex items-center justify-center mx-auto mb-2 shadow-inner">
            <Lock className="w-6 h-6 text-amber-300" />
          </div>
          <h2 className="text-base font-bold text-white">Msantha Farm Security Login</h2>
          <p className="text-xs text-emerald-200/80 mt-0.5">
            Role-Based Access Control • Liwonde Farm System
          </p>
        </div>

        <div className="p-5 space-y-4">
          {errorMsg && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 flex items-center gap-2 animate-in fade-in">
              <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
              <span>{errorMsg}</span>
            </div>
          )}

          {successMsg && (
            <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-bold flex items-center gap-2 animate-in fade-in">
              <Check className="w-4 h-4 text-emerald-600 shrink-0" />
              <span>{successMsg}</span>
            </div>
          )}

          {/* Select Account */}
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-slate-700">Select User Account:</label>
            <div className="grid grid-cols-1 gap-2">
              {users.map((u) => {
                const isSelected = selectedUser.id === u.id;
                const isManager = u.role === 'managing_director' || u.role === 'marketing_manager';
                return (
                  <button
                    key={u.id}
                    type="button"
                    onClick={() => handleSelectUser(u)}
                    className={`flex items-center justify-between p-2.5 rounded-xl border text-left transition ${
                      isSelected
                        ? 'border-emerald-700 bg-emerald-50/70 ring-1 ring-emerald-700'
                        : 'border-slate-200 hover:bg-slate-50'
                    }`}
                  >
                    <div className="flex items-center gap-2.5 min-w-0">
                      <div
                        className="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-xs"
                        style={{ backgroundColor: u.avatarColor || '#1b4332' }}
                      >
                        {u.name.charAt(0)}
                      </div>
                      <div className="min-w-0">
                        <p className="text-xs font-bold text-slate-900 truncate">{u.name}</p>
                        <p className="text-[10px] text-slate-500 truncate">{u.title}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-1.5 shrink-0">
                      <span
                        className={`text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${
                          isManager
                            ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                            : 'bg-amber-100 text-amber-800 border border-amber-300'
                        }`}
                      >
                        {isManager ? 'Full Access' : 'View Only'}
                      </span>
                    </div>
                  </button>
                );
              })}
            </div>
          </div>

          {/* PIN Input */}
          <form onSubmit={handleSubmit} className="space-y-3 pt-1">
            <div className="flex items-center justify-between text-xs font-bold text-slate-700">
              <label>Enter Security PIN:</label>
              <button
                type="button"
                onClick={() => setShowPin(!showPin)}
                className="text-[11px] text-emerald-700 hover:underline flex items-center gap-1 font-semibold"
              >
                {showPin ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                <span>{showPin ? 'Hide PIN' : 'Show PIN'}</span>
              </button>
            </div>

            <div className="flex items-center justify-center gap-2">
              <input
                type={showPin ? 'text' : 'password'}
                maxLength={6}
                value={pin}
                onChange={(e) => setPin(e.target.value.replace(/\D/g, ''))}
                placeholder="••••"
                className="w-full text-center tracking-widest text-lg font-mono font-bold py-2.5 border border-slate-300 rounded-xl focus:outline-hidden focus:ring-2 focus:ring-emerald-700"
                autoFocus
              />
            </div>

            {/* Quick Numeric Keypad */}
            <div className="grid grid-cols-3 gap-1.5 pt-1">
              {['1', '2', '3', '4', '5', '6', '7', '8', '9', 'C', '0', '⌫'].map((k) => (
                <button
                  key={k}
                  type="button"
                  onClick={() => {
                    if (k === 'C') setPin('');
                    else if (k === '⌫') handleBackspace();
                    else handleKeypadPress(k);
                  }}
                  className="py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 active:bg-slate-300 font-semibold text-sm text-slate-800 transition"
                >
                  {k}
                </button>
              ))}
            </div>

            <div className="pt-2 flex gap-2">
              {!isForced && onClose && (
                <button
                  type="button"
                  onClick={onClose}
                  className="w-1/3 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold"
                >
                  Cancel
                </button>
              )}
              <button
                type="submit"
                className={`flex-1 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition ${
                  isForced ? 'w-full' : ''
                }`}
              >
                Sign In to Msantha Farm
              </button>
            </div>
          </form>

          {/* Quick Demo Login Bypass */}
          <div className="pt-3 border-t border-slate-100 text-center space-y-1.5">
            <p className="text-[11px] text-slate-400 font-medium">Quick 1-Tap Login (Staff & Managers):</p>
            <div className="flex flex-wrap justify-center gap-1.5">
              {users.map((u) => (
                <button
                  key={`quick_${u.id}`}
                  type="button"
                  onClick={() => handleQuickDemoLogin(u)}
                  className="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-[10px] font-semibold text-slate-700 flex items-center gap-1"
                >
                  <span>{u.name.split(' ')[0]}</span>
                  <span className="text-[9px] text-slate-400">({u.pin || '1234'})</span>
                </button>
              ))}
            </div>
          </div>
        </div>

      </div>
    </div>
  );
};
