import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { Flock, FlockMovement, PoultrySpecies, AgeGroup, PoultryPurpose } from '../../types';
import { formatAge, formatDate } from '../../utils/i18n';
import { getFlockValuation } from '../../utils/pricing';
import { PoultryPriceCalculatorModal } from '../common/PoultryPriceCalculatorModal';
import {
  Layers,
  Plus,
  History,
  ArrowRightLeft,
  Edit,
  AlertCircle,
  Check,
  Search,
  Filter,
  Calculator,
  Eye,
  Shield,
} from 'lucide-react';

export const FlockManagement: React.FC = () => {
  const { db, currentUser, addFlock, recordFlockMovement, canEditStocks, isViewerOnly, metrics } = useFarmData();

  const [search, setSearch] = useState('');
  const [speciesFilter, setSpeciesFilter] = useState<string>('all');
  const [selectedFlock, setSelectedFlock] = useState<Flock | null>(null);
  const [showAddModal, setShowAddModal] = useState(false);
  const [showMovementModal, setShowMovementModal] = useState(false);
  const [showPriceModal, setShowPriceModal] = useState(false);
  const [priceModalSpecies, setPriceModalSpecies] = useState('mikolongwe');

  // New Flock Form State
  const [flockName, setFlockName] = useState('');
  const [flockSpecies, setFlockSpecies] = useState<PoultrySpecies>('Mikolongwe chicken');
  const [flockBreed, setFlockBreed] = useState('');
  const [flockDateAcquired, setFlockDateAcquired] = useState(new Date().toISOString().split('T')[0]);
  const [flockSupplier, setFlockSupplier] = useState('');
  const [flockInitialQty, setFlockInitialQty] = useState(100);
  const [flockShed, setFlockShed] = useState('Shed 1');
  const [flockPurpose, setFlockPurpose] = useState<PoultryPurpose>('eggs');
  const [flockAgeGroup, setFlockAgeGroup] = useState<AgeGroup>('chicks');
  const [flockNotes, setFlockNotes] = useState('');

  // Movement Form State
  const [movementType, setMovementType] = useState<FlockMovement['type']>('transferred_out');
  const [movementQty, setMovementQty] = useState(10);
  const [movementDest, setMovementDest] = useState('');
  const [movementReason, setMovementReason] = useState('');

  const filteredFlocks = db.flocks.filter((f) => {
    const matchesSearch =
      f.name.toLowerCase().includes(search.toLowerCase()) ||
      f.flockCode.toLowerCase().includes(search.toLowerCase()) ||
      f.housingShed.toLowerCase().includes(search.toLowerCase());
    const matchesSpecies = speciesFilter === 'all' || f.species === speciesFilter;
    return matchesSearch && matchesSpecies;
  });

  const handleCreateFlock = (e: React.FormEvent) => {
    e.preventDefault();
    if (!flockName) return;
    const flockCode = `FLK-2026-${String(db.flocks.length + 1).padStart(3, '0')}`;
    addFlock({
      flockCode,
      name: flockName,
      species: flockSpecies,
      breed: flockBreed || flockSpecies,
      dateAcquired: flockDateAcquired,
      sourceSupplier: flockSupplier || 'Farm Hatchery / Local Supply',
      initialQuantity: Number(flockInitialQty),
      housingShed: flockShed,
      purpose: flockPurpose,
      ageGroup: flockAgeGroup,
      status: 'active',
      notes: flockNotes,
    });
    setShowAddModal(false);
    // Reset form
    setFlockName('');
    setFlockBreed('');
    setFlockSupplier('');
    setFlockNotes('');
  };

  const handleRecordMovement = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedFlock) return;
    recordFlockMovement(
      selectedFlock.id,
      movementType,
      Number(movementQty),
      movementDest,
      movementReason
    );
    setShowMovementModal(false);
  };

  const selectedFlockMovements = selectedFlock
    ? db.movements.filter((m) => m.flockId === selectedFlock.id)
    : [];

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Header & Controls */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-xl font-bold tracking-tight text-slate-900">Flock & Poultry Register</h2>
            {isViewerOnly && (
              <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                View Only
              </span>
            )}
          </div>
          <p className="text-xs text-slate-500">
            Total Live Stock: <strong className="text-slate-800">{metrics.totalLiveBirds.toLocaleString()} birds</strong> • Asset Valuation:{' '}
            <strong className="text-emerald-800">MK {(metrics.totalFlockAssetValuationMwk || 0).toLocaleString()}</strong>
          </p>
        </div>

        <div className="flex items-center gap-2 shrink-0">
          <button
            onClick={() => {
              setPriceModalSpecies('mikolongwe');
              setShowPriceModal(true);
            }}
            className="flex items-center justify-center gap-1.5 px-3 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition"
          >
            <Calculator className="w-3.5 h-3.5" />
            <span>Age-Based Rate Card</span>
          </button>

          {canEditStocks ? (
            <button
              onClick={() => setShowAddModal(true)}
              className="flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
            >
              <Plus className="w-4 h-4" />
              <span>Add New Flock</span>
            </button>
          ) : (
            <div className="px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 text-xs font-semibold flex items-center gap-1.5">
              <Eye className="w-3.5 h-3.5" />
              <span>Stock Add Disabled</span>
            </div>
          )}
        </div>
      </div>

      {/* Search & Filter Bar */}
      <div className="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row gap-2.5 items-center justify-between">
        <div className="relative w-full sm:w-72">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input
            type="text"
            placeholder="Search by flock name, code, shed..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-slate-50 focus:bg-white focus:outline-hidden"
          />
        </div>

        <div className="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
          <Filter className="w-3.5 h-3.5 text-slate-400 shrink-0" />
          <select
            value={speciesFilter}
            onChange={(e) => setSpeciesFilter(e.target.value)}
            className="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white text-slate-700"
          >
            <option value="all">All Species</option>
            <option value="Mikolongwe chicken">Mikolongwe chickens</option>
            <option value="Sasso chicken">Sasso chickens</option>
            <option value="Local chicken">Local chickens</option>
            <option value="Turkey">Turkeys</option>
            <option value="Duck">Ducks</option>
            <option value="Quail">Quails</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>

      {/* Flock Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filteredFlocks.map((flock) => {
          const ageFormatted = formatAge(flock.dateAcquired);
          const isSelected = selectedFlock?.id === flock.id;
          const valuation = getFlockValuation({
            species: flock.species,
            dateAcquired: flock.dateAcquired,
            currentQuantity: flock.currentQuantity,
            ageGroup: flock.ageGroup,
          });

          return (
            <div
              key={flock.id}
              className={`bg-white rounded-xl border p-4 shadow-xs transition hover:shadow-md flex flex-col justify-between ${
                isSelected ? 'border-emerald-600 ring-2 ring-emerald-100' : 'border-slate-200'
              }`}
            >
              <div>
                {/* Top Code & Species */}
                <div className="flex items-center justify-between text-xs mb-1">
                  <span className="font-mono text-[11px] font-bold text-slate-500">{flock.flockCode}</span>
                  <div className="flex items-center gap-1.5">
                    <span className="font-semibold text-emerald-800">{flock.species}</span>
                    <button
                      type="button"
                      onClick={() => {
                        setPriceModalSpecies(flock.species);
                        setShowPriceModal(true);
                      }}
                      className="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-emerald-700 transition"
                      title="View Official Weekly Rate Card"
                    >
                      <Calculator className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>

                <h3 className="text-base font-bold text-slate-900 leading-snug">{flock.name}</h3>
                <p className="text-xs text-slate-500 mt-0.5">Breed: {flock.breed}</p>

                {/* Metrics Grid */}
                <div className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100 text-xs">
                  <div>
                    <span className="text-slate-500 text-[10px] uppercase font-semibold">Current Count</span>
                    <p className="text-lg font-black text-slate-900">
                      {flock.currentQuantity.toLocaleString()}{' '}
                      <span className="text-xs font-normal text-slate-500">/ {flock.initialQuantity}</span>
                    </p>
                  </div>
                  <div>
                    <span className="text-slate-500 text-[10px] uppercase font-semibold">Calculated Age</span>
                    <p className="text-sm font-bold text-slate-800 mt-0.5">{ageFormatted}</p>
                  </div>
                  <div>
                    <span className="text-slate-500 text-[10px] uppercase font-semibold">Housing / Shed</span>
                    <p className="text-xs font-medium text-slate-700 mt-0.5">{flock.housingShed}</p>
                  </div>
                  <div>
                    <span className="text-slate-500 text-[10px] uppercase font-semibold">Purpose</span>
                    <p className="text-xs font-medium text-slate-700 mt-0.5 capitalize">{flock.purpose.replace('_', ' ')}</p>
                  </div>
                </div>

                {/* Age-Based Official Asset Valuation Strip */}
                <div className="mt-3 p-2.5 bg-emerald-50/80 border border-emerald-200/90 rounded-xl flex items-center justify-between text-xs">
                  <div>
                    <span className="text-[10px] text-emerald-800 uppercase font-semibold flex items-center gap-1">
                      Rate ({valuation.ageWeeks} Wks)
                    </span>
                    <p className="font-bold text-emerald-950">
                      MK {valuation.unitPriceMwk.toLocaleString()}{' '}
                      <span className="text-[10px] font-normal text-emerald-700">/ bird</span>
                    </p>
                  </div>
                  <div className="text-right">
                    <span className="text-[10px] text-emerald-800 uppercase font-semibold">Flock Asset Value</span>
                    <p className="font-black text-emerald-900">
                      MK {valuation.totalValuationMwk.toLocaleString()}
                    </p>
                  </div>
                </div>

                {flock.notes && (
                  <p className="mt-2 text-[11px] text-slate-500 italic bg-slate-50 p-2 rounded">
                    {flock.notes}
                  </p>
                )}
              </div>

              {/* Actions Footer */}
              <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                {canEditStocks ? (
                  <button
                    onClick={() => {
                      setSelectedFlock(flock);
                      setShowMovementModal(true);
                    }}
                    className="flex-1 py-1.5 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition"
                  >
                    <ArrowRightLeft className="w-3.5 h-3.5" />
                    <span>Record Movement</span>
                  </button>
                ) : (
                  <button
                    onClick={() => setSelectedFlock(isSelected ? null : flock)}
                    className="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition"
                  >
                    <Eye className="w-3.5 h-3.5" />
                    <span>View History</span>
                  </button>
                )}
                <button
                  onClick={() => setSelectedFlock(isSelected ? null : flock)}
                  className={`py-1.5 px-3 rounded-lg text-xs font-semibold flex items-center gap-1 transition ${
                    isSelected ? 'bg-slate-800 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
                  }`}
                >
                  <History className="w-3.5 h-3.5" />
                  <span>Audit</span>
                </button>
              </div>
            </div>
          );
        })}
      </div>

      {/* Movement Audit Trail Drawer for Selected Flock */}
      {selectedFlock && (
        <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
          <div className="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 className="text-sm font-bold text-slate-900">
                Auditable Movement History: {selectedFlock.name} ({selectedFlock.flockCode})
              </h3>
              <p className="text-xs text-slate-500">
                Every bird entry, transfer, sale, culling, or mortality recorded chronologically.
              </p>
            </div>
            <button
              onClick={() => setSelectedFlock(null)}
              className="text-xs font-semibold text-slate-500 hover:text-slate-800"
            >
              Close History
            </button>
          </div>

          <div className="mt-3 overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                  <th className="p-2.5">Date</th>
                  <th className="p-2.5">Type</th>
                  <th className="p-2.5">Quantity</th>
                  <th className="p-2.5">Change</th>
                  <th className="p-2.5">Reason / Details</th>
                  <th className="p-2.5">Recorded By</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {selectedFlockMovements.map((mov) => (
                  <tr key={mov.id}>
                    <td className="p-2.5 text-slate-700 whitespace-nowrap">{formatDate(mov.date)}</td>
                    <td className="p-2.5 font-semibold text-slate-900 capitalize whitespace-nowrap">
                      {mov.type.replace('_', ' ')}
                    </td>
                    <td className="p-2.5 font-bold text-slate-900">{mov.quantity}</td>
                    <td className="p-2.5 text-slate-600">
                      {mov.previousQuantity} &rarr; <span className="font-bold text-slate-900">{mov.newQuantity}</span>
                    </td>
                    <td className="p-2.5 text-slate-600">{mov.reason || mov.destinationShed || '-'}</td>
                    <td className="p-2.5 text-slate-500">{mov.recordedBy}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Add Flock Modal */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
          <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Register New Flock</h3>
            <p className="text-xs text-slate-500 mb-4">
              Add a new poultry flock to Msantha Farm's active inventory.
            </p>

            <form onSubmit={handleCreateFlock} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Flock Name</label>
                <input
                  type="text"
                  placeholder="e.g. Sasso Broilers Batch 3, Mikolongwe Shed C"
                  value={flockName}
                  onChange={(e) => setFlockName(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Poultry Species</label>
                  <select
                    value={flockSpecies}
                    onChange={(e) => setFlockSpecies(e.target.value as PoultrySpecies)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="Mikolongwe chicken">Mikolongwe chicken</option>
                    <option value="Sasso chicken">Sasso chicken</option>
                    <option value="Local chicken">Local chicken</option>
                    <option value="Turkey">Turkey</option>
                    <option value="Duck">Duck</option>
                    <option value="Quail">Quail</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Breed / Line</label>
                  <input
                    type="text"
                    placeholder="e.g. Indigenous cross, Broad Breasted"
                    value={flockBreed}
                    onChange={(e) => setFlockBreed(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Initial Bird Count</label>
                  <input
                    type="number"
                    min="1"
                    value={flockInitialQty}
                    onChange={(e) => setFlockInitialQty(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Date Acquired / Hatched</label>
                  <input
                    type="date"
                    value={flockDateAcquired}
                    onChange={(e) => setFlockDateAcquired(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Housing / Shed Location</label>
                  <input
                    type="text"
                    placeholder="e.g. Shed 1, Brooder Yard B"
                    value={flockShed}
                    onChange={(e) => setFlockShed(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Purpose</label>
                  <select
                    value={flockPurpose}
                    onChange={(e) => setFlockPurpose(e.target.value as PoultryPurpose)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="eggs">Egg Production</option>
                    <option value="meat">Meat / Broiler</option>
                    <option value="breeding">Parent Stock / Breeding</option>
                    <option value="dual_purpose">Dual Purpose</option>
                    <option value="replacement">Replacement Pullets</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Age Group</label>
                  <select
                    value={flockAgeGroup}
                    onChange={(e) => setFlockAgeGroup(e.target.value as AgeGroup)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="day_old">Day-old chicks</option>
                    <option value="chicks">Chicks</option>
                    <option value="growers">Growers</option>
                    <option value="pullets">Pullets</option>
                    <option value="laying_hens">Laying hens</option>
                    <option value="non_laying_hens">Non-laying hens</option>
                    <option value="breeding_hens">Breeding hens</option>
                    <option value="adult_cocks">Adult cocks</option>
                    <option value="turkey_poults">Turkey poults</option>
                    <option value="adult_turkeys">Adult turkeys</option>
                    <option value="ducklings">Ducklings</option>
                    <option value="adult_ducks">Adult ducks</option>
                    <option value="quail_chicks">Quail chicks</option>
                    <option value="adult_quails">Adult quails</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Source / Supplier</label>
                  <input
                    type="text"
                    placeholder="e.g. Bvumbwe, Farm Hatchery"
                    value={flockSupplier}
                    onChange={(e) => setFlockSupplier(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Notes</label>
                <textarea
                  rows={2}
                  placeholder="Additional health, vaccination, or management notes..."
                  value={flockNotes}
                  onChange={(e) => setFlockNotes(e.target.value)}
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
                  Register Flock
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Record Movement Modal */}
      {showMovementModal && selectedFlock && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">
              Record Bird Movement: {selectedFlock.name}
            </h3>
            <p className="text-xs text-slate-500 mb-4">
              Current live birds: <strong className="text-slate-900">{selectedFlock.currentQuantity}</strong>
            </p>

            <form onSubmit={handleRecordMovement} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Movement Type</label>
                <select
                  value={movementType}
                  onChange={(e) => setMovementType(e.target.value as any)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                >
                  <option value="transferred_out">Transfer Birds to Another Shed</option>
                  <option value="sold">Birds Sold</option>
                  <option value="culled">Culled (Injured or spent)</option>
                  <option value="mortality">Mortality Loss</option>
                  <option value="added">Additional Birds Received</option>
                  <option value="stock_adjustment">Physical Count Reconciliation</option>
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">
                  {movementType === 'stock_adjustment' ? 'Verified Physical Count' : 'Quantity of Birds'}
                </label>
                <input
                  type="number"
                  min="1"
                  value={movementQty}
                  onChange={(e) => setMovementQty(Number(e.target.value))}
                  className="w-full p-2 border border-slate-300 rounded-lg font-bold text-sm"
                  required
                />
              </div>

              {movementType === 'transferred_out' && (
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Destination Shed</label>
                  <input
                    type="text"
                    placeholder="e.g. Shed 3, Free-range Yard"
                    value={movementDest}
                    onChange={(e) => setMovementDest(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
              )}

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Reason / Notes</label>
                <input
                  type="text"
                  placeholder="Reason for adjustment or movement"
                  value={movementReason}
                  onChange={(e) => setMovementReason(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowMovementModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Apply Movement
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Poultry Age-Based Price Calculator Modal */}
      <PoultryPriceCalculatorModal
        isOpen={showPriceModal}
        onClose={() => setShowPriceModal(false)}
        initialSpecies={priceModalSpecies}
      />
    </div>
  );
};
