import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { formatMWK, formatDate, getTranslation } from '../../utils/i18n';
import { QuickOperationsModal, QuickOpType } from './QuickOperationsModal';
import { WhatsAppAlertModal } from '../incubation/WhatsAppAlertModal';
import { CustomerIncubationBatch } from '../../types';
import {
  Layers,
  Egg,
  Flame,
  Wheat,
  TrendingUp,
  AlertTriangle,
  Clock,
  PlusCircle,
  Skull,
  UserPlus,
  ShoppingBag,
  DollarSign,
  CheckCircle2,
  CheckSquare,
  Calendar,
  ChevronRight,
  Sparkles,
  Phone,
  MessageCircle,
  Smartphone,
  Download,
  Calculator,
  Bell,
  UserCheck,
  ShieldCheck,
  Eye,
} from 'lucide-react';
import { ApkDownloadModal } from '../common/ApkDownloadModal';
import { PoultryPriceCalculatorModal } from '../common/PoultryPriceCalculatorModal';
import { AlertCenterModal } from '../common/AlertCenterModal';
import { UserManagementModal } from '../settings/UserManagementModal';

interface DashboardViewProps {
  onNavigate: (tab: any) => void;
}

export const DashboardView: React.FC<DashboardViewProps> = ({ onNavigate }) => {
  const {
    db,
    metrics,
    settings,
    language,
    currentUser,
    updateTaskStatus,
    canEditStocks,
    isViewerOnly,
    activeAlerts,
    canManageUsers,
  } = useFarmData();
  const [quickOpModal, setQuickOpModal] = useState<QuickOpType | null>(null);
  const [selectedWhatsAppBatch, setSelectedWhatsAppBatch] = useState<CustomerIncubationBatch | null>(null);
  const [showApkModal, setShowApkModal] = useState(false);
  const [showPriceModal, setShowPriceModal] = useState(false);
  const [showAlertCenterModal, setShowAlertCenterModal] = useState(false);
  const [showUserMgmtModal, setShowUserMgmtModal] = useState(false);

  // Group birds by species
  const speciesCounts = db.flocks
    .filter((f) => f.status === 'active')
    .reduce((acc, f) => {
      acc[f.species] = (acc[f.species] || 0) + (Number(f.currentQuantity) || 0);
      return acc;
    }, {} as Record<string, number>);

  // Group birds by age group
  const ageGroupCounts = db.flocks
    .filter((f) => f.status === 'active')
    .reduce((acc, f) => {
      acc[f.ageGroup] = (acc[f.ageGroup] || 0) + (Number(f.currentQuantity) || 0);
      return acc;
    }, {} as Record<string, number>);

  const todayIso = new Date().toISOString().split('T')[0];
  const todayTasks = db.tasks.filter((t) => t.dueDate === todayIso);

  return (
    <div className="space-y-6 pb-20 lg:pb-10">
      {/* Top Welcome & Quick Actions Bar */}
      <div className="bg-gradient-to-r from-emerald-950 via-emerald-900 to-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-lg border border-emerald-800/80">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div className="flex items-center gap-2">
              <span className="text-xs uppercase tracking-wider font-semibold text-emerald-300">
                {language === 'ny' ? 'Msantha Poultry Farm • Liwonde' : 'Msantha Poultry Farm • Liwonde'}
              </span>
              <span className="text-emerald-400">·</span>
              <span className="text-xs text-emerald-200">
                {new Date().toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}
              </span>
            </div>
            <h2 className="text-xl sm:text-2xl font-black tracking-tight text-white mt-1">
              {language === 'ny' ? `Takulandirani, ${currentUser.name}` : `Welcome back, ${currentUser.name}`}
            </h2>
            <p className="text-xs text-emerald-200/90 mt-0.5">
              {currentUser.role === 'managing_director'
                ? 'Full executive overview of flock inventory, commercial incubation, and farm financials.'
                : currentUser.role === 'marketing_manager'
                ? 'Managing egg sales, stock additions, delivery logistics, and accounts receivable.'
                : 'Daily farm operations, egg collections, flock health, and incubator monitoring.'}
            </p>

            {/* Live Poultry Inventory Asset Valuation Pill */}
            <div className="mt-2.5 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-900/80 border border-emerald-700/60 text-xs">
              <span className="text-[10px] uppercase font-bold text-emerald-300">Live Bird Asset Value:</span>
              <span className="font-black text-amber-300">
                MK {(metrics.totalFlockAssetValuationMwk || 0).toLocaleString()}
              </span>
              <span className="text-[11px] text-emerald-300">({metrics.totalLiveBirds.toLocaleString()} birds)</span>
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-2 shrink-0">
            <button
              onClick={() => setShowPriceModal(true)}
              className="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-800/90 hover:bg-emerald-700 text-amber-300 font-bold text-xs border border-emerald-700/80 transition"
            >
              <Calculator className="w-3.5 h-3.5" />
              <span>Price Matrix</span>
            </button>

            <button
              onClick={() => setShowAlertCenterModal(true)}
              className={`flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition ${
                activeAlerts.length > 0
                  ? 'bg-rose-600 hover:bg-rose-500 text-white shadow-sm'
                  : 'bg-emerald-800/90 hover:bg-emerald-700 text-emerald-200'
              }`}
            >
              <Bell className="w-3.5 h-3.5" />
              <span>Alerts ({activeAlerts.length})</span>
            </button>

            {canManageUsers && (
              <button
                onClick={() => setShowUserMgmtModal(true)}
                className="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-emerald-300 font-bold text-xs border border-emerald-700/60 transition"
                title="Manage Staff Roles & Access (Managing Director & Sales Manager)"
              >
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                <span>Users & Roles</span>
              </button>
            )}

            {canEditStocks ? (
              <button
                onClick={() => setQuickOpModal('egg_collection')}
                className="flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-md transition active:scale-95 shrink-0"
              >
                <PlusCircle className="w-4 h-4" />
                <span>{language === 'ny' ? 'Zochita Mwachangu' : 'Quick Record'}</span>
              </button>
            ) : (
              <div className="px-3 py-2 rounded-xl bg-slate-800/80 text-amber-300 font-semibold text-xs flex items-center gap-1.5 border border-slate-700">
                <Eye className="w-3.5 h-3.5" />
                <span>Viewing Mode</span>
              </div>
            )}
          </div>
        </div>

        {/* Quick Operations Strip */}
        <div className="mt-4 pt-3 border-t border-emerald-800/60 grid grid-cols-3 sm:grid-cols-6 gap-2">
          <button
            onClick={() => setQuickOpModal('egg_collection')}
            className="flex flex-col items-center p-2 rounded-lg bg-emerald-900/60 hover:bg-emerald-800 text-emerald-100 text-center transition"
          >
            <Egg className="w-4 h-4 text-amber-300 mb-1" />
            <span className="text-[11px] font-semibold leading-tight">Record Eggs</span>
          </button>
          <button
            onClick={() => setQuickOpModal('mortality')}
            className="flex flex-col items-center p-2 rounded-lg bg-emerald-900/60 hover:bg-emerald-800 text-emerald-100 text-center transition"
          >
            <Skull className="w-4 h-4 text-rose-300 mb-1" />
            <span className="text-[11px] font-semibold leading-tight">Mortality</span>
          </button>
          <button
            onClick={() => setQuickOpModal('feed_use')}
            className="flex flex-col items-center p-2 rounded-lg bg-emerald-900/60 hover:bg-emerald-800 text-emerald-100 text-center transition"
          >
            <Wheat className="w-4 h-4 text-amber-300 mb-1" />
            <span className="text-[11px] font-semibold leading-tight">Feed Use</span>
          </button>
          <button
            onClick={() => setQuickOpModal('customer_incubation')}
            className="flex flex-col items-center p-2 rounded-lg bg-emerald-900/60 hover:bg-emerald-800 text-emerald-100 text-center transition"
          >
            <UserPlus className="w-4 h-4 text-cyan-300 mb-1" />
            <span className="text-[11px] font-semibold leading-tight">Cust Incubator</span>
          </button>
          <button
            onClick={() => setQuickOpModal('sale')}
            className="flex flex-col items-center p-2 rounded-lg bg-emerald-900/60 hover:bg-emerald-800 text-emerald-100 text-center transition"
          >
            <ShoppingBag className="w-4 h-4 text-emerald-300 mb-1" />
            <span className="text-[11px] font-semibold leading-tight">Record Sale</span>
          </button>
          <button
            onClick={() => setQuickOpModal('task')}
            className="flex flex-col items-center p-2 rounded-lg bg-emerald-900/60 hover:bg-emerald-800 text-emerald-100 text-center transition"
          >
            <CheckSquare className="w-4 h-4 text-purple-300 mb-1" />
            <span className="text-[11px] font-semibold leading-tight">Add Task</span>
          </button>
        </div>
      </div>

      {/* Android APK Download Card */}
      <div className="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 border border-emerald-700/60 rounded-2xl p-3.5 sm:p-4 text-white shadow-md flex flex-col sm:flex-row items-center justify-between gap-3">
        <div className="flex items-center gap-3 text-center sm:text-left">
          <div className="w-10 h-10 rounded-xl bg-emerald-600/30 border border-emerald-500/40 flex items-center justify-center shrink-0">
            <Smartphone className="w-5 h-5 text-amber-300" />
          </div>
          <div>
            <div className="flex items-center justify-center sm:justify-start gap-2 flex-wrap">
              <span className="text-xs sm:text-sm font-bold text-white">Download Android App (APK)</span>
              <span className="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-400 text-slate-950 uppercase tracking-wide">
                Offline Ready v1.0
              </span>
            </div>
            <p className="text-[11px] text-emerald-200/80 mt-0.5">
              Install the standalone Msantha Farm APK on your Android smartphone for zero-internet rural poultry management in Liwonde.
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2 w-full sm:w-auto shrink-0">
          <button
            onClick={() => setShowApkModal(true)}
            className="flex-1 sm:flex-initial px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-950 font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition active:scale-95 cursor-pointer"
          >
            <Download className="w-3.5 h-3.5" />
            <span>Download APK</span>
          </button>
          <button
            onClick={() => setShowApkModal(true)}
            className="px-3 py-2 rounded-xl bg-emerald-800/80 hover:bg-emerald-700 text-white font-semibold text-xs border border-emerald-600/60 transition"
          >
            Options & QR
          </button>
        </div>
      </div>

      {/* Customer Eggs Ready for Hatch & Collection Alerts */}
      {db.customerIncubations.filter((b) => !b.isCollected && (b.status === 'hatched' || b.status === 'lockdown' || new Date(b.expectedHatchDate).getTime() <= Date.now() + 3 * 86400000)).length > 0 && (
        <div className="bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-900 text-white rounded-2xl p-4 shadow-md border border-emerald-800 space-y-3">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-emerald-800/80">
            <div className="flex items-center gap-2">
              <span className="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping" />
              <h3 className="text-sm font-bold tracking-tight text-white flex items-center gap-1.5">
                <span>Customer Eggs Ready for Hatch & Collection</span>
                <span className="bg-amber-400 text-slate-950 text-[10px] font-black px-1.5 py-0.5 rounded-full">
                  ALERTS
                </span>
              </h3>
            </div>
            <button
              onClick={() => onNavigate('incubation')}
              className="text-xs text-emerald-300 font-semibold hover:underline flex items-center gap-1"
            >
              <span>Manage Incubators</span>
              <ChevronRight className="w-3.5 h-3.5" />
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5 pt-1">
            {db.customerIncubations
              .filter((b) => !b.isCollected && (b.status === 'hatched' || b.status === 'lockdown' || new Date(b.expectedHatchDate).getTime() <= Date.now() + 3 * 86400000))
              .map((batch) => (
                <div
                  key={batch.id}
                  className="bg-emerald-900/50 border border-emerald-700/60 rounded-xl p-3 flex flex-col justify-between gap-2 text-xs"
                >
                  <div className="flex justify-between items-start">
                    <div>
                      <div className="font-bold text-white text-sm">{batch.customerName}</div>
                      <div className="text-emerald-200 text-xs">
                        {batch.species} · {batch.eggsAccepted} eggs · Due: <strong>{formatDate(batch.expectedHatchDate)}</strong>
                      </div>
                      <div className="text-[11px] text-emerald-300/80 mt-0.5">
                        {batch.customerPhone} ({batch.customerVillage || 'Liwonde'})
                      </div>
                    </div>
                    <div className="text-right">
                      <span className="text-[10px] uppercase font-bold text-emerald-300">Balance</span>
                      <div className="text-xs font-black text-amber-300">{formatMWK(batch.balanceDue)}</div>
                    </div>
                  </div>

                  <div className="flex items-center gap-1.5 pt-2 border-t border-emerald-800/60">
                    <button
                      onClick={() => setSelectedWhatsAppBatch(batch)}
                      className="flex-1 py-1 px-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-1 transition"
                    >
                      <MessageCircle className="w-3.5 h-3.5" />
                      <span>WhatsApp Hatch Alert</span>
                    </button>
                    <a
                      href={`tel:${batch.customerPhone}`}
                      className="py-1 px-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1"
                      title="Call customer"
                    >
                      <Phone className="w-3 h-3 text-amber-400" />
                      <span>Call</span>
                    </a>
                  </div>
                </div>
              ))}
          </div>
        </div>
      )}

      {/* Feed Low Stock Alert */}
      {metrics.lowFeedAlerts.length > 0 && (
        <div className="bg-rose-50 border border-rose-200 rounded-xl p-3 flex items-center justify-between gap-2 text-xs">
          <div className="flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 text-rose-600 shrink-0" />
            <span className="font-semibold text-rose-900">
              Low Feed Stock Warning:{' '}
              {metrics.lowFeedAlerts.map((f) => `${f.productName} (${f.currentStockKg}kg left)`).join(', ')}
            </span>
          </div>
          <button
            onClick={() => onNavigate('feed')}
            className="text-rose-700 font-bold hover:underline shrink-0"
          >
            Manage Feed
          </button>
        </div>
      )}

      {/* Cumulative Farm Performance To-Date Strip */}
      <div className="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white rounded-2xl p-4 sm:p-5 border border-emerald-800/80 shadow-md">
        <div className="flex items-center justify-between mb-3 border-b border-emerald-800/60 pb-2">
          <div className="flex items-center gap-2">
            <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse" />
            <h3 className="text-xs sm:text-sm font-bold tracking-wider uppercase text-emerald-300">
              Farm Performance Cumulative To-Date
            </h3>
          </div>
          <span className="text-[11px] text-slate-300 font-medium">All-time farm operations</span>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          {/* Eggs Collected To Date */}
          <div
            onClick={() => onNavigate('eggs')}
            className="bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-3 cursor-pointer transition"
          >
            <div className="text-[10px] uppercase font-bold text-slate-300">Eggs Collected To Date</div>
            <div className="text-xl sm:text-2xl font-black text-amber-300 mt-1">
              {metrics.totalEggsCollectedToDate.toLocaleString()}
            </div>
            <div className="text-[11px] text-slate-300 mt-0.5">
              {Math.floor(metrics.totalEggsCollectedToDate / 30).toLocaleString()} trays (30/tray)
            </div>
          </div>

          {/* Eggs Sold To Date */}
          <div
            onClick={() => onNavigate('sales')}
            className="bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-3 cursor-pointer transition"
          >
            <div className="text-[10px] uppercase font-bold text-slate-300">Eggs Sold To Date</div>
            <div className="text-xl sm:text-2xl font-black text-emerald-400 mt-1">
              {metrics.totalEggTraysSoldToDate.toLocaleString()}{' '}
              <span className="text-xs font-semibold text-slate-300">trays</span>
            </div>
            <div className="text-[11px] text-emerald-300 mt-0.5">
              {formatMWK(metrics.totalEggSalesRevenueToDate)} ({metrics.totalEggsSoldUnitsToDate.toLocaleString()} eggs)
            </div>
          </div>

          {/* Poultry Sales To Date */}
          <div
            onClick={() => onNavigate('sales')}
            className="bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-3 cursor-pointer transition"
          >
            <div className="text-[10px] uppercase font-bold text-slate-300">Poultry Sales To Date</div>
            <div className="text-xl sm:text-2xl font-black text-teal-300 mt-1">
              {metrics.totalPoultryBirdsSoldToDate.toLocaleString()}{' '}
              <span className="text-xs font-semibold text-slate-300">birds</span>
            </div>
            <div className="text-[11px] text-teal-300 mt-0.5">
              {formatMWK(metrics.totalPoultrySalesRevenueToDate)} (Ducks, Quails, DOC, Chickens)
            </div>
          </div>

          {/* Total Sales Revenue To Date */}
          <div
            onClick={() => onNavigate('sales')}
            className="bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-3 cursor-pointer transition"
          >
            <div className="text-[10px] uppercase font-bold text-slate-300">Total Farm Sales To Date</div>
            <div className="text-xl sm:text-2xl font-black text-white mt-1">
              {formatMWK(db.sales.reduce((sum, s) => sum + s.total, 0))}
            </div>
            <div className="text-[11px] text-emerald-300 mt-0.5">
              {formatMWK(db.sales.reduce((sum, s) => sum + s.paymentReceived, 0))} cash received
            </div>
          </div>
        </div>
      </div>

      {/* Main KPI Metric Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        {/* 1. Live Birds */}
        <div
          onClick={() => onNavigate('flocks')}
          className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs hover:border-emerald-300 cursor-pointer transition"
        >
          <div className="flex items-center justify-between text-slate-500 mb-1">
            <span className="text-xs font-semibold">{getTranslation(language, 'liveBirds')}</span>
            <Layers className="w-4 h-4 text-emerald-700" />
          </div>
          <div className="text-2xl font-black text-slate-900">
            {metrics.totalLiveBirds.toLocaleString()}
          </div>
          <div className="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span>{db.flocks.filter((f) => f.status === 'active').length} active flocks</span>
            <span aria-hidden="true">·</span>
            <span className="text-emerald-700 font-medium">Liwonde Sheds</span>
          </div>
        </div>

        {/* 2. Today's Eggs Collected */}
        <div
          onClick={() => onNavigate('eggs')}
          className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs hover:border-amber-300 cursor-pointer transition"
        >
          <div className="flex items-center justify-between text-slate-500 mb-1">
            <span className="text-xs font-semibold">{getTranslation(language, 'eggsCollectedToday')}</span>
            <Egg className="w-4 h-4 text-amber-600" />
          </div>
          <div className="text-2xl font-black text-slate-900">
            {metrics.eggsCollectedToday.toLocaleString()}
          </div>
          <div className="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span className="text-emerald-700 font-semibold">{metrics.saleableEggsToday} saleable</span>
            <span aria-hidden="true">·</span>
            <span>{(metrics.eggsCollectedToday / 30).toFixed(1)} trays</span>
          </div>
        </div>

        {/* 3. Eggs in Stock */}
        <div
          onClick={() => onNavigate('eggs')}
          className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs hover:border-emerald-300 cursor-pointer transition"
        >
          <div className="flex items-center justify-between text-slate-500 mb-1">
            <span className="text-xs font-semibold">{getTranslation(language, 'eggsInStock')}</span>
            <Egg className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-2xl font-black text-emerald-950">
            {metrics.currentEggStockTrays}{' '}
            <span className="text-xs font-semibold text-slate-500">trays</span>
          </div>
          <div className="text-[11px] text-slate-500 mt-1">
            {metrics.currentEggStockUnits} individual eggs
          </div>
        </div>

        {/* 4. Incubating Eggs */}
        <div
          onClick={() => onNavigate('incubation')}
          className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs hover:border-cyan-300 cursor-pointer transition"
        >
          <div className="flex items-center justify-between text-slate-500 mb-1">
            <span className="text-xs font-semibold">{getTranslation(language, 'activeIncubationBatches')}</span>
            <Flame className="w-4 h-4 text-cyan-600" />
          </div>
          <div className="text-2xl font-black text-slate-900">
            {metrics.totalIncubatingEggs.toLocaleString()}
          </div>
          <div className="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span className="text-cyan-800 font-semibold">{metrics.customerEggsInIncubation} customer eggs</span>
            <span aria-hidden="true">·</span>
            <span>{metrics.farmEggsInIncubators} farm</span>
          </div>
        </div>
      </div>

      {/* Secondary Financial & Operational Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
        {/* Today's Sales */}
        <div
          onClick={() => onNavigate('sales')}
          className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs cursor-pointer hover:border-emerald-300 transition"
        >
          <div className="flex items-center justify-between text-slate-500 mb-1">
            <span className="text-xs font-semibold">{getTranslation(language, 'salesToday')}</span>
            <TrendingUp className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-xl font-black text-emerald-800">
            {formatMWK(metrics.todaySalesAmount)}
          </div>
          <div className="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
            <span>Expenses: {formatMWK(metrics.todayExpensesAmount)}</span>
            <ChevronRight className="w-3.5 h-3.5" />
          </div>
        </div>

        {/* Customer Receivables */}
        <div
          onClick={() => onNavigate('sales')}
          className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs cursor-pointer hover:border-amber-300 transition"
        >
          <div className="flex items-center justify-between text-slate-500 mb-1">
            <span className="text-xs font-semibold">{getTranslation(language, 'customerReceivables')}</span>
            <DollarSign className="w-4 h-4 text-amber-600" />
          </div>
          <div className="text-xl font-black text-amber-900">
            {formatMWK(metrics.customerReceivables)}
          </div>
          <div className="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
            <span>Customer batch & sales balances</span>
            <ChevronRight className="w-3.5 h-3.5" />
          </div>
        </div>

        {/* Feed Stock Balance */}
        <div
          onClick={() => onNavigate('feed')}
          className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs cursor-pointer hover:border-slate-300 transition"
        >
          <div className="flex items-center justify-between text-slate-500 mb-1">
            <span className="text-xs font-semibold">{getTranslation(language, 'feedRemaining')}</span>
            <Wheat className="w-4 h-4 text-amber-700" />
          </div>
          <div className="text-xl font-black text-slate-900">
            {metrics.totalFeedStockKg.toLocaleString()}{' '}
            <span className="text-xs font-semibold text-slate-500">kg total</span>
          </div>
          <div className="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
            <span>≈ {(metrics.totalFeedStockKg / 50).toFixed(0)} bags (50kg)</span>
            <ChevronRight className="w-3.5 h-3.5" />
          </div>
        </div>
      </div>

      {/* Two Column Layout: Species Breakdown & Today's Schedule */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Poultry Species Register Breakdown */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
          <div className="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Flock & Species Breakdown</h3>
              <p className="text-[11px] text-slate-500">Live poultry numbers by species across Liwonde sheds</p>
            </div>
            <button
              onClick={() => onNavigate('flocks')}
              className="text-xs font-semibold text-emerald-800 hover:underline flex items-center gap-0.5"
            >
              <span>View Flocks</span>
              <ChevronRight className="w-3.5 h-3.5" />
            </button>
          </div>

          <div className="mt-3 space-y-2.5">
            {Object.entries(speciesCounts).map(([species, count]) => {
              const pct = metrics.totalLiveBirds > 0 ? (count / metrics.totalLiveBirds) * 100 : 0;
              return (
                <div key={species} className="text-xs">
                  <div className="flex justify-between items-center mb-1">
                    <span className="font-semibold text-slate-800">{species}</span>
                    <span className="font-bold text-slate-900">
                      {count.toLocaleString()} birds{' '}
                      <span className="text-[10px] text-slate-400 font-normal">({pct.toFixed(0)}%)</span>
                    </span>
                  </div>
                  <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div
                      className="bg-emerald-700 h-full rounded-full transition-all duration-300"
                      style={{ width: `${pct}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>

          {/* Age groups quiet tags */}
          <div className="mt-4 pt-3 border-t border-slate-100">
            <span className="text-[10px] uppercase tracking-wider font-semibold text-slate-400 block mb-1.5">
              Production & Age Groups
            </span>
            <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600">
              {Object.entries(ageGroupCounts).map(([grp, qty]) => (
                <span key={grp}>
                  <strong className="text-slate-900 font-bold">{qty}</strong> {grp.replace('_', ' ')}
                </span>
              ))}
            </div>
          </div>
        </div>

        {/* Today's Tasks & Schedule */}
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col">
          <div className="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Today's Tasks & Farm Calendar</h3>
              <p className="text-[11px] text-slate-500">
                {todayTasks.filter((t) => t.status === 'completed').length} of {todayTasks.length} tasks completed
              </p>
            </div>
            <button
              onClick={() => onNavigate('tasks')}
              className="text-xs font-semibold text-emerald-800 hover:underline flex items-center gap-0.5"
            >
              <span>All Tasks</span>
              <ChevronRight className="w-3.5 h-3.5" />
            </button>
          </div>

          <div className="mt-3 space-y-2 flex-1 overflow-y-auto max-h-64">
            {todayTasks.length === 0 ? (
              <p className="text-xs text-slate-400 italic py-4 text-center">No tasks scheduled for today.</p>
            ) : (
              todayTasks.map((t) => {
                const isDone = t.status === 'completed';
                return (
                  <div
                    key={t.id}
                    className={`flex items-start justify-between p-2.5 rounded-lg border text-xs transition ${
                      isDone
                        ? 'bg-slate-50 border-slate-200 text-slate-400'
                        : 'bg-white border-slate-200 text-slate-800 hover:border-slate-300'
                    }`}
                  >
                    <div className="flex items-start gap-2.5 min-w-0">
                      <button
                        onClick={() => updateTaskStatus(t.id, isDone ? 'pending' : 'completed')}
                        className={`mt-0.5 rounded-md p-0.5 transition ${
                          isDone ? 'text-emerald-600 bg-emerald-50' : 'text-slate-300 hover:text-slate-600'
                        }`}
                      >
                        <CheckCircle2 className={`w-4 h-4 ${isDone ? 'fill-emerald-600 text-white' : ''}`} />
                      </button>
                      <div className="min-w-0">
                        <p className={`font-semibold truncate ${isDone ? 'line-through text-slate-400' : 'text-slate-800'}`}>
                          {t.title}
                        </p>
                        <p className="text-[10px] text-slate-500 mt-0.5">
                          {t.category.replace('_', ' ')} · Assigned: {t.assignedPerson}
                        </p>
                      </div>
                    </div>

                    <span
                      className={`text-[10px] font-bold px-1.5 py-0.5 rounded shrink-0 ${
                        t.priority === 'high'
                          ? 'bg-rose-100 text-rose-800'
                          : t.priority === 'medium'
                          ? 'bg-amber-100 text-amber-800'
                          : 'bg-slate-100 text-slate-600'
                      }`}
                    >
                      {t.priority}
                    </span>
                  </div>
                );
              })
            )}
          </div>

          <button
            onClick={() => setQuickOpModal('task')}
            className="mt-3 w-full py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-xs rounded-lg transition"
          >
            + Add New Task
          </button>
        </div>
      </div>

      {/* Quick Operation Modal when opened */}
      {quickOpModal && (
        <QuickOperationsModal initialType={quickOpModal} onClose={() => setQuickOpModal(null)} />
      )}

      {/* WhatsApp Hatch Alert Modal */}
      {selectedWhatsAppBatch && (
        <WhatsAppAlertModal
          batch={selectedWhatsAppBatch}
          alertType="hatching_collection"
          settings={settings}
          onClose={() => setSelectedWhatsAppBatch(null)}
        />
      )}

      {/* Android APK Download Modal */}
      <ApkDownloadModal isOpen={showApkModal} onClose={() => setShowApkModal(false)} />

      {/* Poultry Age-Based Pricing Matrix Modal */}
      <PoultryPriceCalculatorModal
        isOpen={showPriceModal}
        onClose={() => setShowPriceModal(false)}
      />

      {/* Alert Center Modal */}
      <AlertCenterModal
        isOpen={showAlertCenterModal}
        onClose={() => setShowAlertCenterModal(false)}
        onNavigateTab={(tab) => {
          setShowAlertCenterModal(false);
          onNavigate(tab);
        }}
      />

      {/* User Management & Security Roles Modal */}
      <UserManagementModal
        isOpen={showUserMgmtModal}
        onClose={() => setShowUserMgmtModal(false)}
      />
    </div>
  );
};
