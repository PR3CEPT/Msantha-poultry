import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { UserProfile, UserRole } from '../../types';
import {
  Shield,
  UserCheck,
  UserX,
  Lock,
  KeyRound,
  Eye,
  Plus,
  Check,
  AlertCircle,
  X,
  ShieldCheck,
  UserPlus,
} from 'lucide-react';

interface UserManagementModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const UserManagementModal: React.FC<UserManagementModalProps> = ({ isOpen, onClose }) => {
  const { users, currentUser, changeUserRole, updateUserPin, addUser } = useFarmData();
  const [editingUserId, setEditingUserId] = useState<string | null>(null);
  const [newPin, setNewPin] = useState('');
  const [statusMsg, setStatusMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // Add User State
  const [showAddUser, setShowAddUser] = useState(false);
  const [addName, setAddName] = useState('');
  const [addUsername, setAddUsername] = useState('');
  const [addTitle, setAddTitle] = useState('');
  const [addRole, setAddRole] = useState<UserRole>('viewer');
  const [addPin, setAddPin] = useState('1234');
  const [addPhone, setAddPhone] = useState('');

  if (!isOpen) return null;

  const handleRoleToggle = (targetUser: UserProfile, makeViewer: boolean) => {
    const targetRole: UserRole = makeViewer ? 'viewer' : 'marketing_manager';
    const res = changeUserRole(targetUser.id, targetRole, makeViewer);
    if (res.success) {
      setStatusMsg({ type: 'success', text: `Role updated for ${targetUser.name}` });
      setTimeout(() => setStatusMsg(null), 2500);
    } else {
      setStatusMsg({ type: 'error', text: res.message });
    }
  };

  const handleUpdatePin = (userId: string) => {
    if (!newPin || newPin.length < 4) {
      setStatusMsg({ type: 'error', text: 'PIN must be at least 4 digits.' });
      return;
    }
    const res = updateUserPin(userId, newPin);
    if (res.success) {
      setStatusMsg({ type: 'success', text: 'PIN successfully changed.' });
      setEditingUserId(null);
      setNewPin('');
      setTimeout(() => setStatusMsg(null), 2500);
    } else {
      setStatusMsg({ type: 'error', text: res.message });
    }
  };

  const handleCreateUser = (e: React.FormEvent) => {
    e.preventDefault();
    if (!addName || !addUsername) {
      setStatusMsg({ type: 'error', text: 'Name and username are required.' });
      return;
    }
    addUser({
      name: addName,
      username: addUsername.toLowerCase().trim(),
      pin: addPin || '1234',
      role: addRole,
      title: addTitle || (addRole === 'viewer' ? 'Operations Viewer' : 'Farm Manager'),
      phone: addPhone,
      avatarColor: addRole === 'viewer' ? '#475569' : '#047857',
      isViewOnly: addRole === 'viewer',
    });
    setStatusMsg({ type: 'success', text: `User ${addName} created successfully!` });
    setShowAddUser(false);
    setAddName('');
    setAddUsername('');
    setAddTitle('');
    setAddPhone('');
    setTimeout(() => setStatusMsg(null), 2500);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
      <div className="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in zoom-in-95 duration-200">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-emerald-950 via-emerald-900 to-slate-900 text-white p-4 sm:p-5 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-400/40 flex items-center justify-center shrink-0">
              <Shield className="w-5 h-5 text-amber-300" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-wider">
                  Security Admin
                </span>
                <span className="text-xs text-emerald-200 font-medium">User Access Management</span>
              </div>
              <h2 className="text-base font-bold text-white mt-0.5">
                Assign User Roles & Security Credentials
              </h2>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 rounded-lg text-emerald-200 hover:text-white hover:bg-emerald-800/80 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="p-4 sm:p-5 max-h-[70vh] overflow-y-auto space-y-4">
          
          {statusMsg && (
            <div
              className={`p-3 rounded-xl text-xs font-semibold flex items-center gap-2 animate-in fade-in ${
                statusMsg.type === 'success'
                  ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                  : 'bg-rose-50 text-rose-800 border border-rose-200'
              }`}
            >
              {statusMsg.type === 'success' ? (
                <Check className="w-4 h-4 text-emerald-600 shrink-0" />
              ) : (
                <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
              )}
              <span>{statusMsg.text}</span>
            </div>
          )}

          {/* Access Policy Explainer */}
          <div className="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 leading-relaxed space-y-1">
            <p className="font-bold text-slate-800 flex items-center gap-1.5">
              <ShieldCheck className="w-4 h-4 text-emerald-700" />
              <span>Role Permissions Matrix:</span>
            </p>
            <ul className="list-disc list-inside text-[11px] space-y-0.5 pl-1">
              <li>
                <strong>Managing Director & Sales & Distribution Manager:</strong> Full access to app (adding/editing stocks, flocks, egg records, sales, finances, and changing user permissions).
              </li>
              <li>
                <strong>Viewer (View-Only):</strong> Can view all records, reports, stock counts, and rate cards, but cannot add, edit, or delete any data.
              </li>
            </ul>
          </div>

          {/* User List */}
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                Current Farm User Profiles ({users.length})
              </h3>
              <button
                type="button"
                onClick={() => setShowAddUser(!showAddUser)}
                className="px-3 py-1 rounded-lg bg-emerald-800 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1 shadow-xs transition"
              >
                <UserPlus className="w-3.5 h-3.5" />
                <span>{showAddUser ? 'Cancel New User' : '+ Add User'}</span>
              </button>
            </div>

            {/* Add User Form */}
            {showAddUser && (
              <form onSubmit={handleCreateUser} className="p-4 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-3 animate-in fade-in">
                <h4 className="text-xs font-bold text-emerald-950">Add New Farm User</h4>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                  <div>
                    <label className="font-semibold text-slate-700">Full Name</label>
                    <input
                      type="text"
                      required
                      placeholder="e.g. John Banda"
                      value={addName}
                      onChange={(e) => setAddName(e.target.value)}
                      className="w-full mt-1 p-2 border border-slate-300 rounded-lg bg-white"
                    />
                  </div>
                  <div>
                    <label className="font-semibold text-slate-700">Username (Login ID)</label>
                    <input
                      type="text"
                      required
                      placeholder="e.g. john"
                      value={addUsername}
                      onChange={(e) => setAddUsername(e.target.value)}
                      className="w-full mt-1 p-2 border border-slate-300 rounded-lg bg-white font-mono"
                    />
                  </div>
                  <div>
                    <label className="font-semibold text-slate-700">Role & Access</label>
                    <select
                      value={addRole}
                      onChange={(e) => setAddRole(e.target.value as UserRole)}
                      className="w-full mt-1 p-2 border border-slate-300 rounded-lg bg-white font-semibold"
                    >
                      <option value="viewer">Viewer (Just Viewing / Read-Only)</option>
                      <option value="marketing_manager">Sales & Distribution Manager (Full Access)</option>
                      <option value="managing_director">Managing Director (Full Access & Admin)</option>
                    </select>
                  </div>
                  <div>
                    <label className="font-semibold text-slate-700">Security PIN (4 digits)</label>
                    <input
                      type="text"
                      maxLength={6}
                      value={addPin}
                      onChange={(e) => setAddPin(e.target.value.replace(/\D/g, ''))}
                      className="w-full mt-1 p-2 border border-slate-300 rounded-lg bg-white font-mono font-bold"
                    />
                  </div>
                </div>

                <div className="flex justify-end gap-2 pt-1">
                  <button
                    type="button"
                    onClick={() => setShowAddUser(false)}
                    className="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-1.5 rounded-lg bg-emerald-800 text-white font-bold text-xs shadow-xs"
                  >
                    Save User Account
                  </button>
                </div>
              </form>
            )}

            <div className="space-y-2">
              {users.map((u) => {
                const isViewer = u.role === 'viewer' || u.isViewOnly === true;
                const isMD = u.role === 'managing_director';
                const isSalesMgr = u.role === 'marketing_manager';
                const isSelf = u.id === currentUser.id;

                return (
                  <div
                    key={u.id}
                    className="p-3.5 bg-white border border-slate-200 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs"
                  >
                    <div className="flex items-center gap-3 min-w-0">
                      <div
                        className="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-xs"
                        style={{ backgroundColor: u.avatarColor || '#1b4332' }}
                      >
                        {u.name.charAt(0)}
                      </div>
                      <div className="min-w-0">
                        <div className="flex items-center gap-2">
                          <p className="text-xs font-bold text-slate-900 truncate">{u.name}</p>
                          {isSelf && (
                            <span className="text-[9px] bg-slate-100 text-slate-600 px-1.5 py-0.2 rounded-md font-semibold">
                              You
                            </span>
                          )}
                        </div>
                        <p className="text-[11px] text-slate-500 truncate">{u.title}</p>
                        <p className="text-[10px] font-mono text-slate-400">
                          Login: <span className="font-bold text-slate-600">{u.username || u.name.toLowerCase()}</span> • PIN: <span className="font-bold text-emerald-800">{u.pin || '1234'}</span>
                        </p>
                      </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end shrink-0">
                      {/* Role Badge & Toggle */}
                      <span
                        className={`text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider ${
                          isViewer
                            ? 'bg-amber-100 text-amber-900 border border-amber-300'
                            : 'bg-emerald-100 text-emerald-900 border border-emerald-300'
                        }`}
                      >
                        {isViewer ? 'Just Viewing' : 'Full Access'}
                      </span>

                      {/* Role change button: allowed for MD and Sales & Distribution Manager */}
                      {!isMD && (
                        <button
                          type="button"
                          onClick={() => handleRoleToggle(u, !isViewer)}
                          className={`px-2.5 py-1 rounded-lg text-xs font-bold transition border ${
                            isViewer
                              ? 'bg-emerald-50 border-emerald-300 text-emerald-800 hover:bg-emerald-100'
                              : 'bg-amber-50 border-amber-300 text-amber-800 hover:bg-amber-100'
                          }`}
                        >
                          {isViewer ? 'Grant Full Access' : 'Set to Viewing Only'}
                        </button>
                      )}

                      {/* Reset PIN inline */}
                      {editingUserId === u.id ? (
                        <div className="flex items-center gap-1">
                          <input
                            type="text"
                            maxLength={6}
                            placeholder="New PIN"
                            value={newPin}
                            onChange={(e) => setNewPin(e.target.value.replace(/\D/g, ''))}
                            className="w-20 px-2 py-1 text-xs font-mono font-bold border border-slate-300 rounded-lg"
                          />
                          <button
                            type="button"
                            onClick={() => handleUpdatePin(u.id)}
                            className="p-1 rounded bg-emerald-800 text-white text-xs"
                          >
                            <Check className="w-3.5 h-3.5" />
                          </button>
                          <button
                            type="button"
                            onClick={() => {
                              setEditingUserId(null);
                              setNewPin('');
                            }}
                            className="p-1 rounded bg-slate-200 text-slate-600 text-xs"
                          >
                            <X className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      ) : (
                        <button
                          type="button"
                          onClick={() => {
                            setEditingUserId(u.id);
                            setNewPin('');
                          }}
                          className="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                          title="Change Login PIN"
                        >
                          <KeyRound className="w-3.5 h-3.5" />
                        </button>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

        </div>

        {/* Footer */}
        <div className="bg-slate-50 px-4 py-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
          <span>Security Audit Active • Liwonde Farm Admin</span>
          <button
            onClick={onClose}
            className="px-4 py-1.5 rounded-lg bg-slate-900 text-white font-semibold text-xs hover:bg-slate-800"
          >
            Done
          </button>
        </div>

      </div>
    </div>
  );
};
