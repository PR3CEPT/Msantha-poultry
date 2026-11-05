/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { Header } from './components/common/Header';
import { Navigation, NavTab } from './components/common/Navigation';
import { OfflineBanner } from './components/common/OfflineBanner';
import { DashboardView } from './components/dashboard/DashboardView';
import { FlockManagement } from './components/flocks/FlockManagement';
import { EggProduction } from './components/eggs/EggProduction';
import { IncubationView } from './components/incubation/IncubationView';
import { CustomerDirectory } from './components/customers/CustomerDirectory';
import { FeedManagement } from './components/feed/FeedManagement';
import { HealthManagement } from './components/health/HealthManagement';
import { SalesDistribution } from './components/sales/SalesDistribution';
import { FinanceCashbook } from './components/finance/FinanceCashbook';
import { TaskManager } from './components/tasks/TaskManager';
import { FarmReports } from './components/reports/FarmReports';
import { SettingsView } from './components/settings/SettingsView';
import { useFarmData } from './hooks/useFarmData';
import { LoginModal } from './components/common/LoginModal';
import { AlertCenterModal } from './components/common/AlertCenterModal';
import { Bell, AlertTriangle, ShieldAlert, Eye, X, ChevronRight } from 'lucide-react';

export default function App() {
  const [currentTab, setCurrentTab] = useState<NavTab>('dashboard');
  const {
    isAuthenticated,
    isViewerOnly,
    currentUser,
    activeAlerts,
    dismissAlert,
  } = useFarmData();

  const [showAlertModal, setShowAlertModal] = useState(false);
  const [dismissedTopBanner, setDismissedTopBanner] = useState(false);

  // Highest priority active alert
  const topCriticalAlert = activeAlerts.find((a) => a.severity === 'critical') || activeAlerts[0];

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans selection:bg-emerald-800 selection:text-white">
      {/* Offline Mode Banner */}
      <OfflineBanner />

      {/* Main Farm Top Header */}
      <Header onNavigateTab={(tab) => setCurrentTab(tab as NavTab)} />

      {/* Pop-Up Notification Banner on Important Alerts */}
      {topCriticalAlert && !dismissedTopBanner && (
        <div className="bg-gradient-to-r from-rose-950 via-slate-900 to-rose-950 text-white border-b border-rose-800/80 px-4 py-2.5 shadow-md flex items-center justify-between gap-3 text-xs animate-in slide-in-from-top-2">
          <div className="flex items-center gap-2.5 min-w-0">
            <span className="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping shrink-0" />
            <span className="px-1.5 py-0.2 rounded-md bg-rose-600 text-white font-black text-[9px] uppercase tracking-wider shrink-0">
              {topCriticalAlert.severity}
            </span>
            <p className="truncate font-medium text-slate-200">
              <strong className="text-white font-bold">{topCriticalAlert.title}:</strong>{' '}
              {topCriticalAlert.description}
            </p>
          </div>

          <div className="flex items-center gap-2 shrink-0">
            <button
              onClick={() => {
                if (topCriticalAlert.actionTab) {
                  setCurrentTab(topCriticalAlert.actionTab as NavTab);
                } else {
                  setShowAlertModal(true);
                }
              }}
              className="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-[11px] flex items-center gap-1 shadow-xs transition"
            >
              <span>{topCriticalAlert.actionLabel || 'View Alert'}</span>
              <ChevronRight className="w-3 h-3" />
            </button>
            <button
              onClick={() => {
                dismissAlert(topCriticalAlert.id);
                setDismissedTopBanner(true);
              }}
              className="p-1 rounded text-slate-400 hover:text-white transition"
              title="Dismiss notification"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </div>
      )}

      {/* View-Only Mode Banner if currentUser is restricted to viewing */}
      {isViewerOnly && (
        <div className="bg-amber-50 border-b border-amber-200 px-4 py-2 text-xs text-amber-900 flex items-center justify-between gap-2 shadow-xs">
          <div className="flex items-center gap-2">
            <Eye className="w-4 h-4 text-amber-700 shrink-0" />
            <span>
              <strong>View-Only Mode:</strong> Logged in as <em>{currentUser.name}</em>. Adding, editing, or deleting stocks and farm records is restricted to Managing Director and Sales & Distribution Manager.
            </span>
          </div>
          <span className="text-[10px] bg-amber-200 text-amber-950 px-2 py-0.5 rounded-md font-bold uppercase shrink-0">
            Read-Only
          </span>
        </div>
      )}

      {/* App Body: Sidebar Navigation + Main Content Area */}
      <div className="flex-1 flex max-w-7xl w-full mx-auto">
        {/* Navigation Sidebar (Desktop) + Mobile Bottom Bar */}
        <Navigation currentTab={currentTab} onSelectTab={setCurrentTab} />

        {/* Dynamic Tab Content Area */}
        <main className="flex-1 min-w-0 p-3.5 sm:p-5 lg:p-6 overflow-y-auto">
          {currentTab === 'dashboard' && <DashboardView onNavigate={setCurrentTab} />}
          {currentTab === 'flocks' && <FlockManagement />}
          {currentTab === 'eggs' && <EggProduction />}
          {currentTab === 'incubation' && <IncubationView />}
          {currentTab === 'customers' && <CustomerDirectory />}
          {currentTab === 'feed' && <FeedManagement />}
          {currentTab === 'health' && <HealthManagement />}
          {currentTab === 'sales' && <SalesDistribution />}
          {currentTab === 'finance' && <FinanceCashbook />}
          {currentTab === 'tasks' && <TaskManager />}
          {currentTab === 'reports' && <FarmReports />}
          {currentTab === 'settings' && <SettingsView />}
        </main>
      </div>

      {/* Forced Login Modal if logged out */}
      <LoginModal
        isOpen={!isAuthenticated}
        isForced={true}
      />

      {/* Alert Center Modal */}
      <AlertCenterModal
        isOpen={showAlertModal}
        onClose={() => setShowAlertModal(false)}
        onNavigateTab={(tab) => setCurrentTab(tab as NavTab)}
      />
    </div>
  );
}
