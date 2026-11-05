import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import {
  LayoutDashboard,
  Layers,
  Egg,
  Flame,
  Users,
  Wheat,
  Activity,
  ShoppingBag,
  DollarSign,
  CheckSquare,
  FileText,
  Settings,
  Menu,
  X,
  Smartphone,
} from 'lucide-react';
import { getTranslation } from '../../utils/i18n';
import { ApkDownloadModal } from './ApkDownloadModal';

export type NavTab =
  | 'dashboard'
  | 'flocks'
  | 'eggs'
  | 'incubation'
  | 'customers'
  | 'feed'
  | 'health'
  | 'sales'
  | 'finance'
  | 'tasks'
  | 'reports'
  | 'settings';

interface NavigationProps {
  currentTab: NavTab;
  onSelectTab: (tab: NavTab) => void;
}

export const Navigation: React.FC<NavigationProps> = ({ currentTab, onSelectTab }) => {
  const { language, metrics, canAccessFinance } = useFarmData();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [showApkModal, setShowApkModal] = useState(false);

  const navItems: { id: NavTab; labelKey: keyof typeof import('../../utils/i18n').translations['en']; icon: React.FC<{ className?: string }>; badge?: number; managerOnly?: boolean }[] = [
    { id: 'dashboard', labelKey: 'dashboard', icon: LayoutDashboard },
    { id: 'flocks', labelKey: 'flocks', icon: Layers },
    { id: 'eggs', labelKey: 'eggs', icon: Egg },
    { id: 'incubation', labelKey: 'incubation', icon: Flame, badge: metrics.hatchesDueSoon.length },
    { id: 'customers', labelKey: 'customers', icon: Users },
    { id: 'feed', labelKey: 'feed', icon: Wheat, badge: metrics.lowFeedAlerts.length },
    { id: 'health', labelKey: 'health', icon: Activity },
    { id: 'sales', labelKey: 'sales', icon: ShoppingBag },
    { id: 'finance', labelKey: 'finance', icon: DollarSign, managerOnly: true },
    { id: 'tasks', labelKey: 'tasks', icon: CheckSquare, badge: metrics.tasksDueTodayCount + metrics.overdueTasksCount },
    { id: 'reports', labelKey: 'reports', icon: FileText },
    { id: 'settings', labelKey: 'settings', icon: Settings },
  ];

  const visibleItems = navItems.filter((item) => !item.managerOnly || canAccessFinance);

  // Bottom navigation primary items (for quick thumb access on Android phones)
  const mobilePrimaryTabs: NavTab[] = ['dashboard', 'flocks', 'eggs', 'incubation', 'sales'];

  return (
    <>
      {/* Desktop / Tablet Sidebar */}
      <aside className="hidden lg:flex flex-col w-64 bg-white border-r border-slate-200 shrink-0 p-3 overflow-y-auto">
        <div className="space-y-1">
          {visibleItems.map((item) => {
            const Icon = item.icon;
            const isActive = currentTab === item.id;
            const label = getTranslation(language, item.labelKey);

            return (
              <button
                key={item.id}
                onClick={() => onSelectTab(item.id)}
                className={`w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-semibold transition-all ${
                  isActive
                    ? 'bg-emerald-800 text-white shadow-xs'
                    : 'text-slate-700 hover:bg-slate-100'
                }`}
              >
                <div className="flex items-center gap-2.5 min-w-0">
                  <Icon className={`w-4 h-4 shrink-0 ${isActive ? 'text-emerald-200' : 'text-slate-500'}`} />
                  <span className="truncate">{label}</span>
                </div>
                {item.badge !== undefined && item.badge > 0 && (
                  <span
                    className={`text-[10px] px-1.5 py-0.5 rounded-full font-bold ${
                      isActive ? 'bg-amber-400 text-slate-900' : 'bg-amber-100 text-amber-800'
                    }`}
                  >
                    {item.badge}
                  </span>
                )}
              </button>
            );
          })}
        </div>

        {/* Liwonde Weather / Location card in sidebar */}
        <div className="mt-auto pt-4 text-[11px] text-slate-500 border-t border-slate-100 px-2 space-y-2">
          <button
            onClick={() => setShowApkModal(true)}
            className="w-full flex items-center justify-center gap-2 py-2 px-2.5 rounded-lg bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-xs"
          >
            <Smartphone className="w-4 h-4 text-amber-300" />
            <span>Download Android APK</span>
          </button>
          <div>
            <p className="font-semibold text-slate-700">Msantha Poultry Farm</p>
            <p>Liwonde, Machinga District</p>
            <p className="text-[10px] text-emerald-700 font-medium">Standalone Android APK v1.0</p>
          </div>
        </div>
      </aside>

      {/* Mobile Persistent Bottom Bar (Android Smartphone friendly) */}
      <nav className="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-2 py-1 shadow-lg flex items-center justify-around safe-bottom">
        {mobilePrimaryTabs.map((tabId) => {
          const item = navItems.find((n) => n.id === tabId);
          if (!item) return null;
          const Icon = item.icon;
          const isActive = currentTab === tabId;
          const label = getTranslation(language, item.labelKey);

          return (
            <button
              key={tabId}
              onClick={() => onSelectTab(tabId)}
              className={`flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-lg transition-colors relative min-h-[46px] ${
                isActive ? 'text-emerald-800 font-bold' : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              <Icon className={`w-5 h-5 ${isActive ? 'stroke-[2.4]' : 'stroke-[1.8]'}`} />
              <span className="text-[10px] tracking-tight truncate max-w-[62px] text-center mt-0.5">
                {label}
              </span>
              {item.badge !== undefined && item.badge > 0 && (
                <span className="absolute top-1 right-2 w-2 h-2 rounded-full bg-amber-500 ring-2 ring-white" />
              )}
            </button>
          );
        })}

        {/* More / Menu Drawer Toggle */}
        <button
          onClick={() => setMobileMenuOpen(true)}
          className={`flex flex-col items-center justify-center flex-1 py-1 px-1 rounded-lg transition-colors relative min-h-[46px] ${
            mobileMenuOpen ? 'text-emerald-800 font-bold' : 'text-slate-500'
          }`}
        >
          <Menu className="w-5 h-5 stroke-[1.8]" />
          <span className="text-[10px] tracking-tight mt-0.5">
            {language === 'ny' ? 'Zina' : 'More'}
          </span>
          {(metrics.tasksDueTodayCount > 0 || metrics.lowFeedAlerts.length > 0) && (
            <span className="absolute top-1 right-2 w-2 h-2 rounded-full bg-amber-500 ring-2 ring-white" />
          )}
        </button>
      </nav>

      {/* Mobile Drawer for Full Modules */}
      {mobileMenuOpen && (
        <div className="lg:hidden fixed inset-0 z-50 flex flex-col justify-end bg-black/60 backdrop-blur-xs">
          <div className="bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto p-4 shadow-2xl animate-in slide-in-from-bottom-5">
            <div className="flex items-center justify-between pb-3 border-b border-slate-200">
              <div>
                <h3 className="text-sm font-bold text-slate-900">
                  {language === 'ny' ? 'Zigawo Zonse za pa Famu' : 'Farm Management Modules'}
                </h3>
                <p className="text-xs text-slate-500">Msantha Poultry Farm • Liwonde</p>
              </div>
              <button
                onClick={() => setMobileMenuOpen(false)}
                className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 bg-slate-100"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="grid grid-cols-2 gap-2 mt-4">
              {visibleItems.map((item) => {
                const Icon = item.icon;
                const isActive = currentTab === item.id;
                const label = getTranslation(language, item.labelKey);

                return (
                  <button
                    key={item.id}
                    onClick={() => {
                      onSelectTab(item.id);
                      setMobileMenuOpen(false);
                    }}
                    className={`flex items-center gap-2.5 p-3 rounded-xl text-left border transition active:scale-98 ${
                      isActive
                        ? 'bg-emerald-800 text-white border-emerald-800 shadow-sm'
                        : 'bg-slate-50 text-slate-800 border-slate-200 hover:bg-slate-100'
                    }`}
                  >
                    <Icon className={`w-4 h-4 shrink-0 ${isActive ? 'text-emerald-200' : 'text-emerald-700'}`} />
                    <div className="min-w-0 flex-1">
                      <div className="text-xs font-semibold truncate">{label}</div>
                      {item.badge !== undefined && item.badge > 0 && (
                        <div className={`text-[10px] ${isActive ? 'text-amber-300' : 'text-amber-700'}`}>
                          {item.badge} pending
                        </div>
                      )}
                    </div>
                  </button>
                );
              })}
            </div>

            {/* Android APK Download Card in Mobile Menu */}
            <div className="mt-4 p-3 bg-emerald-950 text-white rounded-xl border border-emerald-800 flex items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <Smartphone className="w-5 h-5 text-amber-300 shrink-0" />
                <div>
                  <p className="text-xs font-bold text-white leading-tight">Android App APK</p>
                  <p className="text-[10px] text-emerald-300">Offline Standalone Package</p>
                </div>
              </div>
              <button
                onClick={() => {
                  setMobileMenuOpen(false);
                  setShowApkModal(true);
                }}
                className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shrink-0 shadow-xs"
              >
                Download APK
              </button>
            </div>

            <button
              onClick={() => setMobileMenuOpen(false)}
              className="mt-4 w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl"
            >
              {getTranslation(language, 'close')}
            </button>
          </div>
        </div>
      )}

      {/* Android APK Download Modal */}
      <ApkDownloadModal isOpen={showApkModal} onClose={() => setShowApkModal(false)} />
    </>
  );
};
