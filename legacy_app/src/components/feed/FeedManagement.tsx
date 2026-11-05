import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { FeedItem, FeedType, FeedUsage } from '../../types';
import { formatDate, formatMWK } from '../../utils/i18n';
import { Wheat, Plus, AlertTriangle, ArrowDownRight, Edit2, ShieldCheck, History, Eye, Lock } from 'lucide-react';

export const FeedManagement: React.FC = () => {
  const {
    db,
    currentUser,
    metrics,
    addFeedUsage,
    addFeedItem,
    updateFeedStock,
    canEditStocks,
    isViewerOnly,
  } = useFarmData();

  const [showUsageModal, setShowUsageModal] = useState(false);
  const [showItemModal, setShowItemModal] = useState(false);
  const [selectedAdjustItem, setSelectedAdjustItem] = useState<FeedItem | null>(null);

  // Feed Usage Form
  const today = new Date().toISOString().split('T')[0];
  const [useDate, setUseDate] = useState(today);
  const [useFeedId, setUseFeedId] = useState(db.feedItems[0]?.id || '');
  const [useFlockId, setUseFlockId] = useState(db.flocks[0]?.id || '');
  const [useQtyKg, setUseQtyKg] = useState(35);
  const [useNotes, setUseNotes] = useState('');

  // Add Item Form
  const [itemName, setItemName] = useState('');
  const [itemType, setItemType] = useState<FeedType>('layer_feed');
  const [itemSupplier, setItemSupplier] = useState('Farmers World Liwonde');
  const [itemBagSize, setItemBagSize] = useState(50);
  const [itemCostPerBag, setItemCostPerBag] = useState(38500);
  const [itemInitialBags, setItemInitialBags] = useState(10);
  const [itemThresholdKg, setItemThresholdKg] = useState(150);
  const [itemLocation, setItemLocation] = useState('Feed Store Room 1');

  // Adjustment Form
  const [adjustStockKg, setAdjustStockKg] = useState(0);
  const [adjustReason, setAdjustReason] = useState('Physical stock count');

  const handleRecordUsage = (e: React.FormEvent) => {
    e.preventDefault();
    const item = db.feedItems.find((f) => f.id === useFeedId);
    const flock = db.flocks.find((f) => f.id === useFlockId);
    if (!item || !flock) return;

    addFeedUsage({
      date: useDate,
      flockId: flock.id,
      flockName: flock.name,
      feedItemId: item.id,
      productName: item.productName,
      quantityUsedKg: Number(useQtyKg),
      recordedBy: currentUser.name,
      notes: useNotes,
    });

    setShowUsageModal(false);
    setUseNotes('');
  };

  const handleAddItem = (e: React.FormEvent) => {
    e.preventDefault();
    if (!itemName) return;

    addFeedItem({
      productName: itemName,
      feedType: itemType,
      supplier: itemSupplier,
      bagSizeKg: Number(itemBagSize),
      costPerBag: Number(itemCostPerBag),
      currentStockKg: Number(itemInitialBags) * Number(itemBagSize),
      lowStockAlertKg: Number(itemThresholdKg),
      storageLocation: itemLocation,
      lastRestockedDate: today,
    });

    setShowItemModal(false);
    setItemName('');
  };

  const handleSaveAdjustment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedAdjustItem) return;
    updateFeedStock(selectedAdjustItem.id, Number(adjustStockKg), adjustReason);
    setSelectedAdjustItem(null);
  };

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Title & Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">Feed Stock & Consumption</h2>
          <p className="text-xs text-slate-500">
            Monitor 50kg bag inventories, daily rations by flock, reorder alerts, and supplier purchases.
          </p>
        </div>

        {canEditStocks ? (
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowItemModal(true)}
              className="flex items-center gap-1.5 px-3 py-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-xl transition"
            >
              <Plus className="w-4 h-4 text-slate-500" />
              <span>Add Feed Product</span>
            </button>
            <button
              onClick={() => setShowUsageModal(true)}
              className="flex items-center gap-1.5 px-3.5 py-2 bg-amber-800 hover:bg-amber-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
            >
              <Wheat className="w-4 h-4 text-amber-200" />
              <span>Record Feed Use</span>
            </button>
          </div>
        ) : (
          <div className="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-center gap-1.5">
            <Eye className="w-3.5 h-3.5 text-amber-600" />
            <span>Viewing Only (Stock Edits Restricted)</span>
          </div>
        )}
      </div>

      {/* Low Stock Warning Banner */}
      {metrics.lowFeedAlerts.length > 0 && (
        <div className="bg-rose-50 border border-rose-300 rounded-xl p-3.5 flex items-center justify-between gap-3 text-xs">
          <div className="flex items-center gap-2.5">
            <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0" />
            <div>
              <p className="font-bold text-rose-950">
                Low Feed Stock Alert ({metrics.lowFeedAlerts.length} Products)
              </p>
              <p className="text-rose-800 text-[11px] mt-0.5">
                {metrics.lowFeedAlerts.map((f) => `${f.productName}: only ${f.currentStockKg}kg left`).join(' • ')}
              </p>
            </div>
          </div>
          <span className="text-[11px] font-bold text-rose-900 uppercase tracking-wider bg-rose-100 px-2.5 py-1 rounded-lg">
            Reorder Needed
          </span>
        </div>
      )}

      {/* Feed Inventory Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {db.feedItems.map((item) => {
          const bagsRemaining = (item.currentStockKg / (item.bagSizeKg || 50)).toFixed(1);
          const isLow = item.currentStockKg <= item.lowStockAlertKg;

          return (
            <div
              key={item.id}
              className={`bg-white rounded-xl border p-4 shadow-xs flex flex-col justify-between ${
                isLow ? 'border-rose-300 ring-2 ring-rose-100' : 'border-slate-200'
              }`}
            >
              <div>
                <div className="flex items-center justify-between text-xs mb-1">
                  <span className="font-semibold text-amber-800 capitalize">{item.feedType.replace('_', ' ')}</span>
                  {isLow && (
                    <span className="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded">
                      Low Stock
                    </span>
                  )}
                </div>

                <h3 className="text-base font-bold text-slate-900 leading-snug">{item.productName}</h3>
                <p className="text-xs text-slate-500 mt-0.5">Supplier: {item.supplier}</p>

                <div className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100 text-xs">
                  <div>
                    <span className="text-slate-400 text-[10px] uppercase font-semibold">Remaining Stock</span>
                    <p className="text-xl font-black text-slate-900 mt-0.5">
                      {item.currentStockKg.toLocaleString()} <span className="text-xs font-normal">kg</span>
                    </p>
                    <p className="text-[11px] text-slate-500">≈ {bagsRemaining} bags ({item.bagSizeKg}kg)</p>
                  </div>
                  <div>
                    <span className="text-slate-400 text-[10px] uppercase font-semibold">Cost per Bag</span>
                    <p className="text-base font-bold text-slate-800 mt-0.5">{formatMWK(item.costPerBag)}</p>
                    <p className="text-[10px] text-slate-400">Alert at &le; {item.lowStockAlertKg}kg</p>
                  </div>
                </div>

                <div className="mt-3 text-[11px] text-slate-500 flex justify-between bg-slate-50 p-2 rounded">
                  <span>Storage: {item.storageLocation}</span>
                  {item.lastRestockedDate && <span>Restocked: {formatDate(item.lastRestockedDate)}</span>}
                </div>
              </div>

              {/* Adjust Stock Button */}
              {canEditStocks ? (
                <div className="mt-4 pt-3 border-t border-slate-100 flex justify-end">
                  <button
                    onClick={() => {
                      setSelectedAdjustItem(item);
                      setAdjustStockKg(item.currentStockKg);
                    }}
                    className="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg flex items-center gap-1 transition"
                  >
                    <Edit2 className="w-3.5 h-3.5 text-slate-500" />
                    <span>Adjust Stock</span>
                  </button>
                </div>
              ) : (
                <div className="mt-4 pt-3 border-t border-slate-100 flex justify-end">
                  <span className="text-[10px] text-slate-400 italic flex items-center gap-1">
                    <Lock className="w-3 h-3 text-slate-400" />
                    Stock edits managed by MD & Sales Manager
                  </span>
                </div>
              )}
            </div>
          );
        })}
      </div>

      {/* Feed Consumption Log Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="p-3.5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 className="text-sm font-bold text-slate-900">Recent Feed Consumption Log</h3>
            <p className="text-[11px] text-slate-500">Automatic deductions from flock daily feeding logs</p>
          </div>
          <span className="text-xs text-slate-500">{db.feedUsage.length} entries recorded</span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
              <tr>
                <th className="p-3">Date</th>
                <th className="p-3">Flock</th>
                <th className="p-3">Feed Type</th>
                <th className="p-3 text-right">Quantity Used (kg)</th>
                <th className="p-3">Recorded By</th>
                <th className="p-3">Notes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {db.feedUsage.map((usage) => (
                <tr key={usage.id} className="hover:bg-slate-50 transition">
                  <td className="p-3 font-medium text-slate-800 whitespace-nowrap">{formatDate(usage.date)}</td>
                  <td className="p-3 font-semibold text-slate-900">{usage.flockName}</td>
                  <td className="p-3 text-slate-700">{usage.productName}</td>
                  <td className="p-3 text-right font-black text-amber-900">{usage.quantityUsedKg} kg</td>
                  <td className="p-3 text-slate-500">{usage.recordedBy}</td>
                  <td className="p-3 text-slate-500">{usage.notes || '-'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Record Feed Usage Modal */}
      {showUsageModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Record Daily Feed Consumption</h3>
            <p className="text-xs text-slate-500 mb-4">
              Deducts kilograms directly from selected feed inventory.
            </p>

            <form onSubmit={handleRecordUsage} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Date</label>
                  <input
                    type="date"
                    value={useDate}
                    onChange={(e) => setUseDate(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Quantity Used (kg)</label>
                  <input
                    type="number"
                    min="0.5"
                    step="0.5"
                    value={useQtyKg}
                    onChange={(e) => setUseQtyKg(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Feed Product</label>
                <select
                  value={useFeedId}
                  onChange={(e) => setUseFeedId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                >
                  {db.feedItems.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.productName} (Current Stock: {f.currentStockKg}kg)
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Flock</label>
                <select
                  value={useFlockId}
                  onChange={(e) => setUseFlockId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                >
                  {db.flocks.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.name} ({f.housingShed})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Notes</label>
                <input
                  type="text"
                  placeholder="e.g. Mixed with greens, calcium grit supplement"
                  value={useNotes}
                  onChange={(e) => setUseNotes(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowUsageModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-amber-800 hover:bg-amber-700 text-white font-semibold rounded-xl"
                >
                  Save Consumption
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Add Feed Item Modal */}
      {showItemModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Add Feed Product</h3>
            <p className="text-xs text-slate-500 mb-4">Register a new feed brand or formula.</p>

            <form onSubmit={handleAddItem} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Product Name</label>
                <input
                  type="text"
                  placeholder="e.g. Farmers World Broiler Starter (50kg)"
                  value={itemName}
                  onChange={(e) => setItemName(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Feed Type</label>
                  <select
                    value={itemType}
                    onChange={(e) => setItemType(e.target.value as FeedType)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="layer_feed">Layer Feed / Mash</option>
                    <option value="chick_starter">Chick Starter Crumbles</option>
                    <option value="grower_feed">Grower Feed</option>
                    <option value="broiler_starter">Broiler Starter</option>
                    <option value="broiler_finisher">Broiler Finisher</option>
                    <option value="breeder_feed">Breeder Mash</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Supplier</label>
                  <input
                    type="text"
                    value={itemSupplier}
                    onChange={(e) => setItemSupplier(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Cost per Bag (MK)</label>
                  <input
                    type="number"
                    value={itemCostPerBag}
                    onChange={(e) => setItemCostPerBag(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Initial Bags (50kg)</label>
                  <input
                    type="number"
                    value={itemInitialBags}
                    onChange={(e) => setItemInitialBags(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Storage Shed</label>
                  <input
                    type="text"
                    value={itemLocation}
                    onChange={(e) => setItemLocation(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Low Alert (kg)</label>
                  <input
                    type="number"
                    value={itemThresholdKg}
                    onChange={(e) => setItemThresholdKg(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowItemModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Save Product
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Adjust Stock Modal */}
      {selectedAdjustItem && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Adjust Physical Feed Stock</h3>
            <p className="text-xs text-slate-500 mb-4">{selectedAdjustItem.productName}</p>

            <form onSubmit={handleSaveAdjustment} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Verified Stock in Kilograms</label>
                <input
                  type="number"
                  min="0"
                  step="0.5"
                  value={adjustStockKg}
                  onChange={(e) => setAdjustStockKg(Number(e.target.value))}
                  className="w-full p-2 border border-slate-300 rounded-lg font-bold text-base"
                  required
                />
                <span className="text-[10px] text-slate-500">
                  ≈ {(adjustStockKg / (selectedAdjustItem.bagSizeKg || 50)).toFixed(1)} bags
                </span>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Reason for Adjustment</label>
                <input
                  type="text"
                  value={adjustReason}
                  onChange={(e) => setAdjustReason(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setSelectedAdjustItem(null)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Apply Adjustment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
