import React, { useState, useMemo } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { formatDate, formatMWK } from '../../utils/i18n';
import { Egg, Plus, Calendar, TrendingUp, AlertTriangle, ShieldCheck, Download, Eye } from 'lucide-react';

export const EggProduction: React.FC = () => {
  const { db, settings, currentUser, metrics, addEggCollection, canEditStocks, isViewerOnly } = useFarmData();

  const [dateFilter, setDateFilter] = useState('');
  const [flockFilter, setFlockFilter] = useState('all');
  const [showAddModal, setShowAddModal] = useState(false);

  // Form State
  const today = new Date().toISOString().split('T')[0];
  const [collectionDate, setCollectionDate] = useState(today);
  const [collectionTime, setCollectionTime] = useState(new Date().toTimeString().substring(0, 5));
  const [flockId, setFlockId] = useState(db.flocks[0]?.id || '');
  const [totalCollected, setTotalCollected] = useState(240);
  const [saleableCount, setSaleableCount] = useState(220);
  const [crackedCount, setCrackedCount] = useState(8);
  const [dirtyCount, setDirtyCount] = useState(6);
  const [damagedCount, setDamagedCount] = useState(6);
  const [farmUsedCount, setFarmUsedCount] = useState(10);
  const [reservedIncubationCount, setReservedIncubationCount] = useState(20);
  const [notes, setNotes] = useState('');

  const filteredCollections = useMemo(() => {
    return db.eggCollections.filter((c) => {
      const matchDate = !dateFilter || c.collectionDate === dateFilter;
      const matchFlock = flockFilter === 'all' || c.flockId === flockFilter;
      return matchDate && matchFlock;
    });
  }, [db.eggCollections, dateFilter, flockFilter]);

  // Aggregate stats from filtered collections
  const totalFilteredEggs = filteredCollections.reduce((sum, c) => sum + c.totalCollected, 0);
  const totalFilteredSaleable = filteredCollections.reduce((sum, c) => sum + c.saleableCount, 0);
  const totalFilteredCracked = filteredCollections.reduce((sum, c) => sum + c.crackedCount, 0);
  const totalFilteredDamaged = filteredCollections.reduce((sum, c) => sum + c.damagedRejectedCount, 0);
  const totalFilteredIncubation = filteredCollections.reduce((sum, c) => sum + (c.reservedIncubationCount || 0), 0);
  const averageSaleableRate = totalFilteredEggs > 0 ? (totalFilteredSaleable / totalFilteredEggs) * 100 : 0;

  const handleCreateCollection = (e: React.FormEvent) => {
    e.preventDefault();
    const flock = db.flocks.find((f) => f.id === flockId);
    if (!flock) return;

    addEggCollection({
      collectionDate,
      collectionTime,
      flockId: flock.id,
      flockName: flock.name,
      species: flock.species,
      breed: flock.breed,
      totalCollected: Number(totalCollected),
      saleableCount: Number(saleableCount),
      crackedCount: Number(crackedCount),
      dirtyCount: Number(dirtyCount),
      damagedRejectedCount: Number(damagedCount),
      farmUsedCount: Number(farmUsedCount),
      reservedIncubationCount: Number(reservedIncubationCount),
      recordedBy: currentUser.name,
      notes,
    });

    setShowAddModal(false);
    setNotes('');
  };

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Module Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">Egg Production & Collection</h2>
          <p className="text-xs text-slate-500">
            Daily collection register, tray conversion, quality grading, and current saleable stock inventory.
          </p>
        </div>

        {canEditStocks ? (
          <button
            onClick={() => setShowAddModal(true)}
            className="flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
          >
            <Plus className="w-4 h-4" />
            <span>Record Egg Collection</span>
          </button>
        ) : (
          <div className="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-center gap-1.5">
            <Eye className="w-3.5 h-3.5 text-amber-600" />
            <span>Viewing Only</span>
          </div>
        )}
      </div>

      {/* To-Date Cumulative Egg Metrics */}
      <div className="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white rounded-2xl p-4 shadow-sm border border-emerald-900/60">
        <div className="flex items-center justify-between mb-2.5 pb-2 border-b border-emerald-800/40">
          <span className="text-[11px] font-bold tracking-wider uppercase text-emerald-300">
            Egg Production & Distribution Cumulative To-Date
          </span>
          <span className="text-[10px] text-slate-400">All historical logs</span>
        </div>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
          <div className="bg-white/5 border border-white/10 rounded-xl p-3">
            <span className="text-[10px] uppercase font-bold text-slate-300">Eggs Collected To Date</span>
            <div className="text-xl sm:text-2xl font-black text-amber-300 mt-0.5">
              {metrics.totalEggsCollectedToDate.toLocaleString()}
            </div>
            <p className="text-[11px] text-slate-300 mt-0.5">
              {Math.floor(metrics.totalEggsCollectedToDate / 30).toLocaleString()} trays (30/tray)
            </p>
          </div>
          <div className="bg-white/5 border border-white/10 rounded-xl p-3">
            <span className="text-[10px] uppercase font-bold text-slate-300">Saleable Eggs Collected</span>
            <div className="text-xl sm:text-2xl font-black text-emerald-400 mt-0.5">
              {metrics.totalSaleableEggsCollectedToDate.toLocaleString()}
            </div>
            <p className="text-[11px] text-slate-300 mt-0.5">
              {metrics.totalEggsCollectedToDate > 0
                ? `${((metrics.totalSaleableEggsCollectedToDate / metrics.totalEggsCollectedToDate) * 100).toFixed(1)}%`
                : '100%'} quality yield
            </p>
          </div>
          <div className="bg-white/5 border border-white/10 rounded-xl p-3">
            <span className="text-[10px] uppercase font-bold text-slate-300">Eggs Sold To Date</span>
            <div className="text-xl sm:text-2xl font-black text-teal-300 mt-0.5">
              {metrics.totalEggTraysSoldToDate.toLocaleString()} <span className="text-xs font-semibold">trays</span>
            </div>
            <p className="text-[11px] text-slate-300 mt-0.5">
              {metrics.totalEggsSoldUnitsToDate.toLocaleString()} individual eggs
            </p>
          </div>
          <div className="bg-white/5 border border-white/10 rounded-xl p-3">
            <span className="text-[10px] uppercase font-bold text-slate-300">Egg Revenue To Date</span>
            <div className="text-xl sm:text-2xl font-black text-white mt-0.5">
              {formatMWK(metrics.totalEggSalesRevenueToDate)}
            </div>
            <p className="text-[11px] text-emerald-300 mt-0.5">
              Fertilized & table egg trays
            </p>
          </div>
        </div>
      </div>

      {/* Stock Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-slate-500">Available Egg Stock</span>
          <div className="text-2xl font-black text-emerald-950 mt-1">
            {metrics.currentEggStockTrays}{' '}
            <span className="text-xs font-semibold text-slate-500">trays</span>
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            {metrics.currentEggStockUnits} individual eggs in storage
          </p>
        </div>

        <div className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-slate-500">Today's Total Collected</span>
          <div className="text-2xl font-black text-slate-900 mt-1">
            {metrics.eggsCollectedToday.toLocaleString()}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            ≈ {(metrics.eggsCollectedToday / (settings.defaultTraySize || 30)).toFixed(1)} trays collected
          </p>
        </div>

        <div className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-slate-500">Quality & Saleable Rate</span>
          <div className="text-2xl font-black text-emerald-700 mt-1">
            {metrics.eggsCollectedToday > 0
              ? `${((metrics.saleableEggsToday / metrics.eggsCollectedToday) * 100).toFixed(1)}%`
              : '—'}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            {metrics.crackedEggsToday} cracked · {metrics.rejectedEggsToday} rejected today
          </p>
        </div>

        <div className="bg-white rounded-xl p-4 border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-slate-500">Egg Value Estimate</span>
          <div className="text-xl font-black text-slate-900 mt-1">
            {formatMWK(metrics.currentEggStockUnits * (settings.eggUnitPriceMwk || 150))}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            At MK {settings.trayPriceMwk || 4500} / tray
          </p>
        </div>
      </div>

      {/* Filter and Table Controls */}
      <div className="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row gap-2.5 items-center justify-between">
        <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          <div>
            <label htmlFor="filter-flock" className="sr-only">Filter by flock</label>
            <select
              id="filter-flock"
              value={flockFilter}
              onChange={(e) => setFlockFilter(e.target.value)}
              className="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white text-slate-700"
            >
              <option value="all">All Laying Flocks</option>
              {db.flocks.map((f) => (
                <option key={f.id} value={f.id}>
                  {f.name}
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-center gap-1.5 text-xs text-slate-600">
            <Calendar className="w-3.5 h-3.5 text-slate-400" />
            <input
              type="date"
              value={dateFilter}
              onChange={(e) => setDateFilter(e.target.value)}
              className="border border-slate-300 rounded-lg px-2 py-1 text-xs bg-slate-50"
            />
            {dateFilter && (
              <button
                onClick={() => setDateFilter('')}
                className="text-[10px] text-slate-500 underline ml-1"
              >
                Clear
              </button>
            )}
          </div>
        </div>

        <div className="text-xs text-slate-500 font-medium">
          Showing {filteredCollections.length} collection logs ({totalFilteredEggs} eggs)
        </div>
      </div>

      {/* Daily Egg Collection Register Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
              <tr>
                <th className="p-3">Date & Time</th>
                <th className="p-3">Flock & Species</th>
                <th className="p-3 text-right">Total</th>
                <th className="p-3 text-right">Trays (30)</th>
                <th className="p-3 text-right text-emerald-800">Saleable</th>
                <th className="p-3 text-right text-amber-700">Cracked</th>
                <th className="p-3 text-right text-rose-700">Damaged</th>
                <th className="p-3 text-right">Farm Used</th>
                <th className="p-3 text-right">Incubation</th>
                <th className="p-3">Recorded By</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredCollections.length === 0 ? (
                <tr>
                  <td colSpan={10} className="p-6 text-center text-slate-400 italic">
                    No egg collection records found for the selected filter.
                  </td>
                </tr>
              ) : (
                filteredCollections.map((col) => {
                  const trays = (col.totalCollected / (settings.defaultTraySize || 30)).toFixed(1);
                  const saleablePct =
                    col.totalCollected > 0 ? ((col.saleableCount / col.totalCollected) * 100).toFixed(0) : 0;

                  return (
                    <tr key={col.id} className="hover:bg-slate-50/80 transition">
                      <td className="p-3 whitespace-nowrap">
                        <div className="font-semibold text-slate-900">{formatDate(col.collectionDate)}</div>
                        <div className="text-[10px] text-slate-400">{col.collectionTime}</div>
                      </td>
                      <td className="p-3">
                        <div className="font-semibold text-slate-800">{col.flockName}</div>
                        <div className="text-[10px] text-slate-500">{col.species}</div>
                      </td>
                      <td className="p-3 text-right font-black text-slate-900">{col.totalCollected}</td>
                      <td className="p-3 text-right font-medium text-slate-700">{trays}</td>
                      <td className="p-3 text-right font-bold text-emerald-800">
                        {col.saleableCount}{' '}
                        <span className="text-[10px] text-emerald-600 font-normal">({saleablePct}%)</span>
                      </td>
                      <td className="p-3 text-right text-amber-800">{col.crackedCount}</td>
                      <td className="p-3 text-right text-rose-700">{col.damagedRejectedCount}</td>
                      <td className="p-3 text-right text-slate-600">{col.farmUsedCount || 0}</td>
                      <td className="p-3 text-right text-cyan-800 font-medium">
                        {col.reservedIncubationCount || 0}
                      </td>
                      <td className="p-3 text-slate-500 whitespace-nowrap">{col.recordedBy}</td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add Egg Collection Modal */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
          <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Record Daily Egg Collection</h3>
            <p className="text-xs text-slate-500 mb-4">
              Enter collection numbers and quality grading. Egg inventory will be updated automatically.
            </p>

            <form onSubmit={handleCreateCollection} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Collection Date</label>
                  <input
                    type="date"
                    value={collectionDate}
                    onChange={(e) => setCollectionDate(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Collection Time</label>
                  <input
                    type="time"
                    value={collectionTime}
                    onChange={(e) => setCollectionTime(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Select Flock</label>
                <select
                  value={flockId}
                  onChange={(e) => setFlockId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                >
                  {db.flocks.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.name} ({f.species} • {f.housingShed})
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Total Eggs Collected</label>
                  <input
                    type="number"
                    min="1"
                    value={totalCollected}
                    onChange={(e) => {
                      const tot = Number(e.target.value);
                      setTotalCollected(tot);
                      setSaleableCount(Math.max(0, tot - crackedCount - dirtyCount - damagedCount));
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold text-sm"
                    required
                  />
                  <span className="text-[10px] text-slate-500">
                    ≈ {(totalCollected / 30).toFixed(1)} trays (30 eggs/tray)
                  </span>
                </div>
                <div>
                  <label className="block font-semibold text-emerald-800 mb-1">Saleable Eggs</label>
                  <input
                    type="number"
                    value={saleableCount}
                    onChange={(e) => setSaleableCount(Number(e.target.value))}
                    className="w-full p-2 border border-emerald-400 bg-emerald-50/50 rounded-lg font-bold text-sm text-emerald-950"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                <div>
                  <label className="block text-[10px] font-medium text-slate-600 mb-0.5">Cracked Shells</label>
                  <input
                    type="number"
                    value={crackedCount}
                    onChange={(e) => setCrackedCount(Number(e.target.value))}
                    className="w-full p-1.5 border border-slate-300 rounded bg-white"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-medium text-slate-600 mb-0.5">Dirty / Stained</label>
                  <input
                    type="number"
                    value={dirtyCount}
                    onChange={(e) => setDirtyCount(Number(e.target.value))}
                    className="w-full p-1.5 border border-slate-300 rounded bg-white"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-medium text-rose-700 mb-0.5">Damaged / Reject</label>
                  <input
                    type="number"
                    value={damagedCount}
                    onChange={(e) => setDamagedCount(Number(e.target.value))}
                    className="w-full p-1.5 border border-rose-300 rounded bg-white font-semibold text-rose-900"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-medium text-slate-700 mb-1">Farm Kitchen / Staff Use</label>
                  <input
                    type="number"
                    value={farmUsedCount}
                    onChange={(e) => setFarmUsedCount(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-medium text-slate-700 mb-1">Set Aside for Incubation</label>
                  <input
                    type="number"
                    value={reservedIncubationCount}
                    onChange={(e) => setReservedIncubationCount(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                  <span className="text-[10px] text-slate-400">Not counted as available sale stock</span>
                </div>
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Notes</label>
                <input
                  type="text"
                  placeholder="e.g. Shell quality, nesting box cleanliness, etc."
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
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
                  Save Collection
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
