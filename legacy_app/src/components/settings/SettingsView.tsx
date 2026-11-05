import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import {
  exportDatabaseBackup,
  restoreDatabaseBackup,
  resetToSeedData,
  clearAllFarmData,
} from '../../services/storage';
import { formatDate } from '../../utils/i18n';
import {
  Settings,
  Download,
  Upload,
  RefreshCw,
  Trash2,
  ShieldAlert,
  Save,
  CheckCircle,
  FileCode,
  MapPin,
  Clock,
  UserCheck,
  Smartphone,
  ShieldCheck,
  Shield,
  Eye,
  Lock,
  KeyRound,
  UserPlus,
  AlertCircle,
  Store,
  Sparkles,
  ExternalLink,
} from 'lucide-react';
import { ApkDownloadModal } from '../common/ApkDownloadModal';
import { UserManagementModal } from './UserManagementModal';

export const SettingsView: React.FC = () => {
  const {
    settings,
    updateSettings,
    db,
    currentUser,
    users,
    canAccessFullAdmin,
    canManageUsers,
    isViewerOnly,
    changeUserRole,
  } = useFarmData();
  const [showApkModal, setShowApkModal] = useState(false);
  const [apkModalTab, setApkModalTab] = useState<'apk' | 'playstore' | 'privacy' | 'webapk' | 'source' | 'qr'>('apk');
  const [showUserModal, setShowUserModal] = useState(false);
  const [showClearConfirmModal, setShowClearConfirmModal] = useState(false);
  const [clearConfirmText, setClearConfirmText] = useState('');

  const [farmName, setFarmName] = useState(settings.farmName);
  const [location, setLocation] = useState(settings.location);
  const [md, setMd] = useState(settings.managingDirector);
  const [marketingManager, setMarketingManager] = useState(settings.marketingManager);
  const [phone, setPhone] = useState(settings.phoneContact);
  const [email, setEmail] = useState(settings.emailContact);
  const [traySize, setTraySize] = useState(settings.defaultTraySize || 30);
  const [trayPrice, setTrayPrice] = useState(settings.trayPriceMwk || 4500);
  const [eggPrice, setEggPrice] = useState(settings.eggUnitPriceMwk || 150);

  // Status message
  const [saveStatus, setSaveStatus] = useState<string | null>(null);
  const [restoreStatus, setRestoreStatus] = useState<string | null>(null);

  const handleSaveSettings = (e: React.FormEvent) => {
    e.preventDefault();
    updateSettings({
      farmName,
      location,
      managingDirector: md,
      marketingManager,
      phoneContact: phone,
      emailContact: email,
      defaultTraySize: Number(traySize),
      trayPriceMwk: Number(trayPrice),
      eggUnitPriceMwk: Number(eggPrice),
    });
    setSaveStatus('Farm settings updated successfully!');
    setTimeout(() => setSaveStatus(null), 2500);
  };

  const handleFileRestore = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      const content = event.target?.result as string;
      const res = restoreDatabaseBackup(content);
      if (res.success) {
        setRestoreStatus('Farm database successfully restored from file!');
      } else {
        setRestoreStatus(`Restore error: ${res.message}`);
      }
      setTimeout(() => setRestoreStatus(null), 3500);
    };
    reader.readAsText(file);
  };

  return (
    <div className="space-y-6 pb-20 lg:pb-10 max-w-4xl">
      {/* Title */}
      <div>
        <h2 className="text-xl font-bold tracking-tight text-slate-900">Farm Settings & System Administration</h2>
        <p className="text-xs text-slate-500">
          Configure business details, offline backups, data restore, species durations, and security audits.
        </p>
      </div>

      {saveStatus && (
        <div className="bg-emerald-600 text-white p-3 rounded-xl text-xs font-bold flex items-center gap-2">
          <CheckCircle className="w-4 h-4" />
          <span>{saveStatus}</span>
        </div>
      )}

      {restoreStatus && (
        <div className="bg-cyan-700 text-white p-3 rounded-xl text-xs font-bold flex items-center gap-2">
          <CheckCircle className="w-4 h-4" />
          <span>{restoreStatus}</span>
        </div>
      )}

      {/* Security, User Roles & Access Control Card */}
      <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-emerald-900 text-emerald-100 flex items-center justify-center">
              <ShieldCheck className="w-4 h-4 text-emerald-400" />
            </div>
            <div>
              <h3 className="text-sm font-bold text-slate-900">User Access & Staff Role Permissions</h3>
              <p className="text-[11px] text-slate-500">
                Managing Director & Sales Manager have Full Access (stock edits, finances). Other users can be set to View-Only.
              </p>
            </div>
          </div>

          {canManageUsers && (
            <button
              onClick={() => setShowUserModal(true)}
              className="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-800 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition"
            >
              <UserPlus className="w-3.5 h-3.5 text-amber-300" />
              <span>Manage & Add Users</span>
            </button>
          )}
        </div>

        {/* Current Active User Status Indicator */}
        <div className="p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
          <div className="flex items-center gap-2.5">
            <div className={`w-3 h-3 rounded-full ${isViewerOnly ? 'bg-amber-500' : 'bg-emerald-500 ring-2 ring-emerald-200'}`} />
            <div>
              <div className="flex items-center gap-2">
                <span className="font-bold text-slate-900">{currentUser.name}</span>
                <span className="text-slate-400">(@{currentUser.username || 'staff'})</span>
                <span
                  className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                    currentUser.role === 'managing_director'
                      ? 'bg-emerald-100 text-emerald-900 border border-emerald-300'
                      : currentUser.role === 'marketing_manager'
                      ? 'bg-blue-100 text-blue-900 border border-blue-300'
                      : 'bg-amber-100 text-amber-900 border border-amber-300'
                  }`}
                >
                  {currentUser.role === 'managing_director'
                    ? 'Managing Director'
                    : currentUser.role === 'marketing_manager'
                    ? 'Sales & Distribution Manager'
                    : 'Staff (Viewing)'}
                </span>
              </div>
              <p className="text-[11px] text-slate-500 mt-0.5">
                {currentUser.role === 'managing_director' || currentUser.role === 'marketing_manager'
                  ? 'Full administrative privileges: add/edit bird stocks, feed, sales records, cashbook, and change user viewing permissions.'
                  : 'View-Only permissions active. You can browse live metrics and reports, but data modifications are restricted.'}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <span
              className={`px-2.5 py-1 rounded-lg text-xs font-bold ${
                isViewerOnly
                  ? 'bg-amber-100 text-amber-800 border border-amber-300'
                  : 'bg-emerald-100 text-emerald-900 border border-emerald-300'
              }`}
            >
              {isViewerOnly ? '👁️ Viewing Mode' : '⚡ Full Access'}
            </span>
          </div>
        </div>

        {/* If user is MD or Sales Mgr, show instant user list and 1-tap view-only toggle */}
        {canManageUsers ? (
          <div className="space-y-2">
            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">
              Staff Accounts & Access Control ({users.length} Users)
            </span>
            <div className="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
              {users.map((u) => {
                const isFullAccess = u.role === 'managing_director' || u.role === 'marketing_manager';
                const isCurrent = u.id === currentUser.id;

                return (
                  <div key={u.id} className="p-3 flex items-center justify-between hover:bg-slate-50 transition text-xs gap-3">
                    <div className="min-w-0">
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-slate-800">{u.name}</span>
                        {isCurrent && (
                          <span className="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">
                            You
                          </span>
                        )}
                        <span className="text-slate-400 text-[11px]">(@{u.username || 'user'})</span>
                      </div>
                      <p className="text-[11px] text-slate-500 mt-0.5">{u.title || u.role.replace('_', ' ')}</p>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                      <span
                        className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                          isFullAccess
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-amber-100 text-amber-800'
                        }`}
                      >
                        {isFullAccess ? 'Full Access' : 'Viewing Only'}
                      </span>

                      {!isCurrent && (
                        <button
                          onClick={() => {
                            if (isFullAccess) {
                              changeUserRole(u.id, 'viewer', true);
                            } else {
                              changeUserRole(u.id, 'marketing_manager', false);
                            }
                          }}
                          className={`px-2.5 py-1 rounded-lg text-xs font-semibold transition ${
                            isFullAccess
                              ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300'
                              : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300'
                          }`}
                        >
                          {isFullAccess ? 'Set to Viewing' : 'Grant Full Access'}
                        </button>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        ) : (
          <div className="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2">
            <AlertCircle className="w-4 h-4 text-amber-600 shrink-0" />
            <p>
              User role assignments and permissions can only be modified by the Managing Director (Isaac Chipeta) or the Sales & Distribution Manager (Sikizwayo Chipeta).
            </p>
          </div>
        )}
      </div>

      {/* Farm Profile Configuration */}
      <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
        <h3 className="text-sm font-bold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
          <Settings className="w-4 h-4 text-emerald-800" />
          <span>Farm Identity & Contacts</span>
        </h3>

        <form onSubmit={handleSaveSettings} className="space-y-3.5 text-xs">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Farm Name</label>
              <input
                type="text"
                value={farmName}
                onChange={(e) => setFarmName(e.target.value)}
                className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                required
              />
            </div>
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Location</label>
              <input
                type="text"
                value={location}
                onChange={(e) => setLocation(e.target.value)}
                className="w-full p-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Managing Director</label>
              <input
                type="text"
                value={md}
                onChange={(e) => setMd(e.target.value)}
                className="w-full p-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Distribution & Marketing Manager</label>
              <input
                type="text"
                value={marketingManager}
                onChange={(e) => setMarketingManager(e.target.value)}
                className="w-full p-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Phone Numbers</label>
              <input
                type="text"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full p-2 border border-slate-300 rounded-lg"
              />
            </div>
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Email</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full p-2 border border-slate-300 rounded-lg"
              />
            </div>
          </div>

          <div className="grid grid-cols-3 gap-3 pt-2">
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Eggs per Tray</label>
              <input
                type="number"
                value={traySize}
                onChange={(e) => setTraySize(Number(e.target.value))}
                className="w-full p-2 border border-slate-300 rounded-lg font-bold"
              />
            </div>
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Default Tray Price (MK)</label>
              <input
                type="number"
                value={trayPrice}
                onChange={(e) => setTrayPrice(Number(e.target.value))}
                className="w-full p-2 border border-slate-300 rounded-lg font-bold"
              />
            </div>
            <div>
              <label className="block font-semibold text-slate-700 mb-1">Single Egg Price (MK)</label>
              <input
                type="number"
                value={eggPrice}
                onChange={(e) => setEggPrice(Number(e.target.value))}
                className="w-full p-2 border border-slate-300 rounded-lg font-bold"
              />
            </div>
          </div>

          <div className="flex justify-end pt-3">
            <button
              type="submit"
              className="flex items-center gap-1.5 px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition"
            >
              <Save className="w-4 h-4" />
              <span>Save Settings</span>
            </button>
          </div>
        </form>
      </div>

      {/* System Reset, Fresh Data Setup & Database Restore Hub */}
      <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-emerald-950 text-white flex items-center justify-center">
              <RefreshCw className="w-4 h-4 text-emerald-400" />
            </div>
            <div>
              <h3 className="text-sm font-bold text-slate-900">System Reset, Fresh Data & Restore Hub</h3>
              <p className="text-[11px] text-slate-500">
                Clear all records to start fresh operations, reload templates, or restore from a backup file.
              </p>
            </div>
          </div>
          <span className="text-[10px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
            Storage Engine v1.0
          </span>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
          {/* Card 1: Clear All System Data & Put Fresh Data */}
          <div className="p-4 bg-rose-50/70 border border-rose-200 rounded-xl space-y-2 flex flex-col justify-between">
            <div>
              <div className="flex items-center gap-2">
                <Trash2 className="w-4 h-4 text-rose-700" />
                <span className="font-bold text-rose-950 text-xs">Clear All System Data (Put Fresh Data)</span>
              </div>
              <p className="text-[11px] text-rose-800 mt-1">
                Wipes all existing flocks, egg collections, incubation batches, sales invoices, and expenses. Keeps your system settings and user credentials intact so you can enter 100% fresh, live farm data.
              </p>
            </div>
            <button
              onClick={() => {
                setClearConfirmText('');
                setShowClearConfirmModal(true);
              }}
              className="w-full py-2.5 px-3 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg flex items-center justify-center gap-1.5 shadow-xs transition active:scale-95"
            >
              <Trash2 className="w-3.5 h-3.5" />
              <span>Clear All System Data & Start Fresh</span>
            </button>
          </div>

          {/* Card 2: Restore from Backup File */}
          <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2 flex flex-col justify-between">
            <div>
              <div className="flex items-center gap-2">
                <Upload className="w-4 h-4 text-slate-700" />
                <span className="font-bold text-slate-900 text-xs">Restore from Backup File (.json)</span>
              </div>
              <p className="text-[11px] text-slate-600 mt-1">
                Upload a previously saved backup file (.json) from your device, SD card, or Google Drive to restore all farm records instantly.
              </p>
            </div>
            <label className="w-full py-2.5 px-3 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-lg flex items-center justify-center gap-1.5 cursor-pointer transition active:scale-95 shadow-xs">
              <Upload className="w-3.5 h-3.5 text-amber-300" />
              <span>Restore Database from File</span>
              <input
                type="file"
                accept=".json"
                onChange={handleFileRestore}
                className="hidden"
              />
            </label>
          </div>
        </div>

        {/* Secondary Actions: Export & Demo Template Restore */}
        <div className="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
          <button
            onClick={exportDatabaseBackup}
            className="w-full py-2 px-3 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-lg flex items-center justify-center gap-1.5 transition"
          >
            <Download className="w-3.5 h-3.5" />
            <span>Download Database Backup (.json)</span>
          </button>

          <button
            onClick={() => {
              if (confirm('Restore standard Msantha Poultry Farm demonstration records with sample flocks, sales, and incubations?')) {
                resetToSeedData();
                setRestoreStatus('Msantha Farm demonstration records successfully restored!');
                setTimeout(() => window.location.reload(), 1200);
              }
            }}
            className="w-full py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold rounded-lg flex items-center justify-center gap-1.5 transition border border-slate-200"
          >
            <RefreshCw className="w-3.5 h-3.5 text-emerald-800" />
            <span>Restore Demo Template Data</span>
          </button>
        </div>
      </div>

      {/* Android APK & Mobile Distribution Hub */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-emerald-800 text-white flex items-center justify-center">
              <Smartphone className="w-4 h-4 text-amber-300" />
            </div>
            <div>
              <h3 className="text-sm font-bold text-slate-900">Android Application & Offline APK</h3>
              <p className="text-[11px] text-slate-500">
                Official distribution package for Msantha Farm staff smartphones (Liwonde & Machinga)
              </p>
            </div>
          </div>
          <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 flex items-center gap-1">
            <ShieldCheck className="w-3.5 h-3.5 text-emerald-700" />
            Signed Release
          </span>
        </div>

        <div className="p-4 space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div className="p-3 rounded-lg bg-slate-50 border border-slate-100">
              <span className="text-[10px] uppercase font-bold text-slate-400">Package Name</span>
              <p className="font-mono text-xs font-semibold text-slate-800 mt-0.5">com.msanthafarm.app</p>
            </div>
            <div className="p-3 rounded-lg bg-slate-50 border border-slate-100">
              <span className="text-[10px] uppercase font-bold text-slate-400">Target Platform</span>
              <p className="font-mono text-xs font-semibold text-slate-800 mt-0.5">Android 5.0 - 15 (API 21-35)</p>
            </div>
            <div className="p-3 rounded-lg bg-slate-50 border border-slate-100">
              <span className="text-[10px] uppercase font-bold text-slate-400">Build Signature</span>
              <p className="font-mono text-[11px] font-semibold text-emerald-700 mt-0.5">v1, v2, v3 Verified RSA-2048</p>
            </div>
          </div>

          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
            <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
              <a
                href="/msantha-poultry-farm.apk"
                download="msantha-poultry-farm.apk"
                className="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition active:scale-95"
              >
                <Download className="w-4 h-4 text-amber-300" />
                <span>Download Standalone APK (860 KB)</span>
              </a>

              <a
                href="/downloads/msantha-farm-android-source.zip"
                download="msantha-farm-android-source.zip"
                className="flex-1 sm:flex-initial px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center justify-center gap-1.5 transition"
              >
                <FileCode className="w-3.5 h-3.5 text-slate-500" />
                <span>Source Code (.zip)</span>
              </a>
            </div>

            <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
              <button
                onClick={() => {
                  setApkModalTab('privacy');
                  setShowApkModal(true);
                }}
                className="flex-1 sm:flex-initial px-3.5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-sm active:scale-95"
              >
                <ShieldCheck className="w-4 h-4 text-emerald-400" />
                <span>Privacy & Security Protection</span>
              </button>

              <button
                onClick={() => {
                  setApkModalTab('playstore');
                  setShowApkModal(true);
                }}
                className="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-700 text-white font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-sm active:scale-95"
              >
                <Store className="w-4 h-4 text-amber-300" />
                <span>Google Play Store Upload Kit</span>
              </button>

              <button
                onClick={() => {
                  setApkModalTab('apk');
                  setShowApkModal(true);
                }}
                className="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs"
              >
                <Smartphone className="w-4 h-4" />
                <span>Direct APK & Easy Install</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Privacy, Security & Google Play Protect Hub */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-gradient-to-r from-emerald-950/5 via-slate-50 to-white">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-emerald-900 text-white flex items-center justify-center shadow-xs">
              <ShieldCheck className="w-4 h-4 text-amber-300" />
            </div>
            <div>
              <h3 className="text-sm font-bold text-slate-900">Privacy, Security & Google Play Protect Hub</h3>
              <p className="text-[11px] text-slate-500">
                Malawi Data Protection Act (DPA 2024), GDPR compliance, and modern Android 12–15 security standards
              </p>
            </div>
          </div>
          <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 self-start sm:self-auto">
            100% Zero-Telemetry Certified
          </span>
        </div>

        <div className="p-4 space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div className="p-3 rounded-lg bg-slate-50 border border-slate-100 space-y-1">
              <div className="flex items-center gap-1.5 text-slate-900 font-bold">
                <Lock className="w-3.5 h-3.5 text-emerald-700" />
                <span>On-Device Isolation</span>
              </div>
              <p className="text-[11px] text-slate-600">
                All farm cash flow, egg collections, and flock incubation data remain strictly sandboxed on this smartphone.
              </p>
            </div>

            <div className="p-3 rounded-lg bg-slate-50 border border-slate-100 space-y-1">
              <div className="flex items-center gap-1.5 text-slate-900 font-bold">
                <Shield className="w-3.5 h-3.5 text-emerald-700" />
                <span>Zero Intrusive Rights</span>
              </div>
              <p className="text-[11px] text-slate-600">
                No access to camera, microphone, contacts, location, or private photos. Target SDK 34 Scoped Storage compliant.
              </p>
            </div>

            <div className="p-3 rounded-lg bg-slate-50 border border-slate-100 space-y-1">
              <div className="flex items-center gap-1.5 text-slate-900 font-bold">
                <CheckCircle className="w-3.5 h-3.5 text-emerald-700" />
                <span>RSA-2048 Signed APK</span>
              </div>
              <p className="text-[11px] text-slate-600">
                v1, v2, v3 verified signing scheme allows smooth bypass of Play Protect warnings via "More details" ➔ "Install anyway".
              </p>
            </div>
          </div>

          <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <div className="space-y-0.5">
              <p className="font-bold text-slate-900">
                Installing on new Android phone (Samsung OneUI 6, Xiaomi HyperOS, Tecno HiOS)?
              </p>
              <p className="text-[11px] text-slate-600">
                Read our illustrated step-by-step installation guide to breeze through Play Protect and unknown source prompts.
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-2 shrink-0">
              <button
                onClick={() => {
                  setApkModalTab('apk');
                  setShowApkModal(true);
                }}
                className="px-3.5 py-2 rounded-xl bg-emerald-800 hover:bg-emerald-700 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-2xs"
              >
                <Smartphone className="w-3.5 h-3.5 text-amber-300" />
                <span>New Phone Install Guide</span>
              </button>

              <a
                href="/privacy-policy.html"
                target="_blank"
                rel="noreferrer"
                className="px-3.5 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 font-semibold text-xs transition flex items-center gap-1.5"
              >
                <ExternalLink className="w-3.5 h-3.5 text-slate-500" />
                <span>Full Privacy Policy (2026)</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      {/* Audit Log Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="p-3.5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 className="text-sm font-bold text-slate-900">System Security & Audit Trail</h3>
            <p className="text-[11px] text-slate-500">Auditable log of user actions, flock adjustments, and settings</p>
          </div>
          <span className="text-xs text-slate-400">{db.auditLogs.length} logged events</span>
        </div>

        <div className="overflow-x-auto max-h-72 overflow-y-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold sticky top-0">
              <tr>
                <th className="p-2.5">Date / Time</th>
                <th className="p-2.5">User</th>
                <th className="p-2.5">Action</th>
                <th className="p-2.5">Module</th>
                <th className="p-2.5">Details</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {db.auditLogs.map((log) => (
                <tr key={log.id} className="hover:bg-slate-50">
                  <td className="p-2.5 text-slate-500 whitespace-nowrap">
                    {formatDate(log.date)} {new Date(log.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                  </td>
                  <td className="p-2.5 font-semibold text-slate-800">{log.user}</td>
                  <td className="p-2.5 font-mono text-[10px] text-slate-600 font-bold">{log.action}</td>
                  <td className="p-2.5 uppercase text-[10px] text-slate-500">{log.module}</td>
                  <td className="p-2.5 text-slate-700">{log.details}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Android APK Download & Google Play Store Modal */}
      <ApkDownloadModal
        isOpen={showApkModal}
        initialTab={apkModalTab}
        onClose={() => setShowApkModal(false)}
      />

      {/* Clear All System Data Confirmation Modal */}
      {showClearConfirmModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-rose-200 animate-in fade-in my-auto space-y-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                <ShieldAlert className="w-6 h-6 text-rose-600" />
              </div>
              <div>
                <h3 className="text-base font-bold text-slate-900">Clear All System Data?</h3>
                <p className="text-xs text-rose-700 font-semibold">Prepare Database for Fresh Live Operations</p>
              </div>
            </div>

            <div className="text-xs text-slate-600 space-y-2 bg-rose-50/60 p-3.5 rounded-xl border border-rose-200">
              <p>
                <strong>What will be erased:</strong>
              </p>
              <ul className="list-disc list-inside space-y-1 text-slate-700">
                <li>All active and past flock records & movements</li>
                <li>All egg collection logs and inventory balances</li>
                <li>All commercial customer & farm incubation batches</li>
                <li>All sales invoices, deliveries, and payment receipts</li>
                <li>All feed item stocks and consumption logs</li>
                <li>All mortality and flock health treatment records</li>
              </ul>
              <p className="pt-1 text-[11px] text-emerald-800 font-medium">
                ✓ Your farm profile settings and staff user login accounts will be safely preserved.
              </p>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Type <span className="font-mono text-rose-700 font-bold bg-rose-100 px-1.5 py-0.5 rounded">CLEAR</span> to confirm:
              </label>
              <input
                type="text"
                value={clearConfirmText}
                onChange={(e) => setClearConfirmText(e.target.value)}
                placeholder="Type CLEAR"
                className="w-full p-2 border border-slate-300 rounded-lg text-xs font-mono font-bold"
              />
            </div>

            <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
              <button
                type="button"
                onClick={() => {
                  setShowClearConfirmModal(false);
                  setClearConfirmText('');
                }}
                className="px-4 py-2 border border-slate-300 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition"
              >
                Cancel
              </button>
              <button
                type="button"
                disabled={clearConfirmText.trim().toUpperCase() !== 'CLEAR'}
                onClick={() => {
                  clearAllFarmData();
                  setShowClearConfirmModal(false);
                  setClearConfirmText('');
                  setRestoreStatus('All system records cleared! The farm database is now 100% fresh and ready for live data.');
                  setTimeout(() => window.location.reload(), 1200);
                }}
                className="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:hover:bg-rose-600 text-white text-xs font-bold rounded-xl shadow-xs transition"
              >
                Yes, Wipe & Put Fresh Data
              </button>
            </div>
          </div>
        </div>
      )}

      {/* User Management & Security Roles Modal */}
      <UserManagementModal
        isOpen={showUserModal}
        onClose={() => setShowUserModal(false)}
      />

      {/* Android APK, Google Play & Privacy Hub Modal */}
      <ApkDownloadModal
        isOpen={showApkModal}
        onClose={() => setShowApkModal(false)}
        initialTab={apkModalTab}
      />
    </div>
  );
};
