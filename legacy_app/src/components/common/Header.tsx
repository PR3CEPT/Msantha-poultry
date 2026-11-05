import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { PWAInstallButton } from './PWAInstallButton';
import {
  User,
  MapPin,
  Shield,
  Bell,
  Lock,
  Calculator,
  UserCheck,
  ShieldCheck,
  KeyRound,
} from 'lucide-react';
import { AlertCenterModal } from './AlertCenterModal';
import { LoginModal } from './LoginModal';
import { PoultryPriceCalculatorModal } from './PoultryPriceCalculatorModal';
import { UserManagementModal } from '../settings/UserManagementModal';

interface HeaderProps {
  onNavigateTab?: (tab: string) => void;
}

export const Header: React.FC<HeaderProps> = ({ onNavigateTab }) => {
  const {
    settings,
    currentUser,
    users,
    switchUser,
    language,
    setLanguage,
    activeAlerts,
    isManagingDirector,
    isSalesManager,
    isViewerOnly,
    canManageUsers,
  } = useFarmData();

  const [showAlertModal, setShowAlertModal] = useState(false);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showPriceModal, setShowPriceModal] = useState(false);
  const [showUserMgmtModal, setShowUserMgmtModal] = useState(false);

  const criticalAlertsCount = activeAlerts.filter((a) => a.severity === 'critical').length;

  return (
    <header className="bg-emerald-950 text-emerald-50 border-b border-emerald-900 shadow-md sticky top-0 z-40">
      <div className="max-w-7xl mx-auto px-3 sm:px-4 py-2.5 flex items-center justify-between gap-2">
        {/* Left: Farm Logo & Name */}
        <div className="flex items-center gap-2.5 min-w-0">
          <div className="w-9 h-9 rounded-lg bg-emerald-800 border border-emerald-700/60 p-1 flex items-center justify-center shrink-0 shadow-inner">
            <img src="/icon.svg" alt="Msantha Farm" className="w-full h-full object-contain" />
          </div>
          <div className="min-w-0">
            <div className="flex items-center gap-1.5 flex-wrap">
              <h1 className="text-sm sm:text-base font-bold tracking-tight text-white truncate">
                {settings.farmName}
              </h1>
              <span className="hidden md:inline-flex items-center gap-1 text-[11px] text-emerald-300/90 font-medium">
                <MapPin className="w-3 h-3 text-emerald-400" />
                {settings.location}
              </span>
            </div>
            <p className="text-[11px] text-emerald-300/80 truncate">
              {language === 'ny' ? 'Kayendetsedwe ka Ziweto & Kuswa Mazira' : 'Poultry Operations & Incubation'}
            </p>
          </div>
        </div>

        {/* Right: Actions, Rate Card, Alert Bell, Role Selector, PWA */}
        <div className="flex items-center gap-1.5 sm:gap-2 shrink-0">
          
          {/* Poultry Price Matrix & Rate Card Button */}
          <button
            onClick={() => setShowPriceModal(true)}
            className="flex items-center gap-1.5 px-2 sm:px-2.5 py-1.5 rounded-lg bg-emerald-900/90 hover:bg-emerald-800 text-amber-300 text-xs font-bold border border-emerald-800 transition shadow-xs active:scale-95"
            title="Open Live Poultry Age-Based Price Calculator"
          >
            <Calculator className="w-3.5 h-3.5 text-amber-400" />
            <span className="hidden md:inline">Price Matrix</span>
          </button>

          {/* Important Alert Notification Bell Pop-up Trigger */}
          <button
            onClick={() => setShowAlertModal(true)}
            className={`relative p-1.5 sm:px-2.5 sm:py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 ${
              activeAlerts.length > 0
                ? 'bg-rose-900/80 hover:bg-rose-800 text-white border border-rose-700 shadow-sm'
                : 'bg-emerald-900/80 hover:bg-emerald-800 text-emerald-200 border border-emerald-800'
            }`}
            title={`${activeAlerts.length} active alerts. Click to view alert center.`}
          >
            <Bell className={`w-3.5 h-3.5 ${criticalAlertsCount > 0 ? 'text-amber-300 animate-pulse' : ''}`} />
            {activeAlerts.length > 0 && (
              <span className="flex items-center gap-1">
                <span className="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-rose-500 text-white shadow-xs">
                  {activeAlerts.length}
                </span>
                <span className="hidden sm:inline text-[11px]">Alerts</span>
              </span>
            )}
          </button>

          {/* PWA Install / APK Download Button */}
          <PWAInstallButton />

          {/* Language Toggle (EN / Chichewa) */}
          <div className="flex items-center bg-emerald-900/80 border border-emerald-800 rounded-lg p-0.5 text-xs font-semibold">
            <button
              onClick={() => setLanguage('en')}
              className={`px-1.5 sm:px-2 py-1 rounded transition-colors ${
                language === 'en'
                  ? 'bg-emerald-700 text-white shadow-xs'
                  : 'text-emerald-300 hover:text-white'
              }`}
              title="English"
            >
              EN
            </button>
            <button
              onClick={() => setLanguage('ny')}
              className={`px-1.5 sm:px-2 py-1 rounded transition-colors ${
                language === 'ny'
                  ? 'bg-emerald-700 text-white shadow-xs'
                  : 'text-emerald-300 hover:text-white'
              }`}
              title="Chichewa (Malawi)"
            >
              NY
            </button>
          </div>

          {/* User Profile & Security Switcher */}
          <div className="flex items-center gap-1">
            <div className="relative">
              <label htmlFor="user-select" className="sr-only">Switch active user</label>
              <div className="flex items-center gap-1.5 bg-emerald-900/90 hover:bg-emerald-800/90 border border-emerald-700/60 rounded-lg px-2 sm:px-2.5 py-1 text-xs transition">
                <Shield className={`w-3.5 h-3.5 shrink-0 ${isViewerOnly ? 'text-amber-400' : 'text-emerald-400'}`} />
                <select
                  id="user-select"
                  value={currentUser.id}
                  onChange={(e) => switchUser(e.target.value)}
                  className="bg-transparent text-emerald-100 font-medium focus:outline-hidden cursor-pointer max-w-[90px] sm:max-w-[170px] truncate"
                >
                  {users.map((u) => {
                    const isMgr = u.role === 'managing_director' || u.role === 'marketing_manager';
                    return (
                      <option key={u.id} value={u.id} className="bg-slate-900 text-white">
                        {u.name} ({isMgr ? 'Full Access' : 'Viewing'})
                      </option>
                    );
                  })}
                </select>
              </div>
            </div>

            {/* Quick Security Lock / Keypad button */}
            <button
              onClick={() => setShowLoginModal(true)}
              className="p-1.5 rounded-lg bg-emerald-900 hover:bg-emerald-800 text-emerald-300 hover:text-white border border-emerald-800 transition"
              title="Enter PIN / Security Lock"
            >
              <Lock className="w-3.5 h-3.5 text-amber-400" />
            </button>

            {/* Manage Users Button for MD and Sales & Distribution Manager */}
            {canManageUsers && (
              <button
                onClick={() => setShowUserMgmtModal(true)}
                className="hidden lg:flex p-1.5 rounded-lg bg-emerald-900 hover:bg-emerald-800 text-emerald-300 hover:text-white border border-emerald-800 transition"
                title="Manage Roles (Set Other Users to Viewing)"
              >
                <UserCheck className="w-3.5 h-3.5 text-emerald-300" />
              </button>
            )}
          </div>

        </div>
      </div>

      {/* Pop-up Modals */}
      <AlertCenterModal
        isOpen={showAlertModal}
        onClose={() => setShowAlertModal(false)}
        onNavigateTab={onNavigateTab}
      />

      <LoginModal
        isOpen={showLoginModal}
        onClose={() => setShowLoginModal(false)}
      />

      <PoultryPriceCalculatorModal
        isOpen={showPriceModal}
        onClose={() => setShowPriceModal(false)}
      />

      <UserManagementModal
        isOpen={showUserMgmtModal}
        onClose={() => setShowUserMgmtModal(false)}
      />
    </header>
  );
};
