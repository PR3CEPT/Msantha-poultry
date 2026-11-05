import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { X, Egg, Skull, Wheat, Flame, UserPlus, ShoppingBag, DollarSign, CheckSquare, PlusCircle } from 'lucide-react';
import { PoultrySpecies, AgeGroup, PaymentMethod, TaskCategory, ProductCategory } from '../../types';

export type QuickOpType =
  | 'egg_collection'
  | 'mortality'
  | 'feed_use'
  | 'new_flock'
  | 'customer_incubation'
  | 'sale'
  | 'expense'
  | 'task';

interface QuickOperationsModalProps {
  initialType?: QuickOpType;
  onClose: () => void;
}

export const QuickOperationsModal: React.FC<QuickOperationsModalProps> = ({ initialType = 'egg_collection', onClose }) => {
  const {
    db,
    settings,
    currentUser,
    addEggCollection,
    addMortalityRecord,
    addFeedUsage,
    addFlock,
    addCustomerIncubationBatch,
    addSale,
    addExpense,
    addTask,
  } = useFarmData();

  const [activeType, setActiveType] = useState<QuickOpType>(initialType);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  // Form states
  const today = new Date().toISOString().split('T')[0];

  // Egg Collection State
  const [eggFlockId, setEggFlockId] = useState(db.flocks[0]?.id || '');
  const [eggTotal, setEggTotal] = useState(250);
  const [eggSaleable, setEggSaleable] = useState(235);
  const [eggCracked, setEggCracked] = useState(6);
  const [eggDirty, setEggDirty] = useState(4);
  const [eggDamaged, setEggDamaged] = useState(5);
  const [eggFarmUsed, setEggFarmUsed] = useState(10);
  const [eggReservedInc, setEggReservedInc] = useState(30);

  // Mortality State
  const [mortFlockId, setMortFlockId] = useState(db.flocks[0]?.id || '');
  const [mortCount, setMortCount] = useState(1);
  const [mortCause, setMortCause] = useState('Unknown');
  const [mortSymptoms, setMortSymptoms] = useState('');
  const [mortAction, setMortAction] = useState('Removed and safely disposed, isolated flock for monitoring');

  // Feed Use State
  const [feedItemId, setFeedItemId] = useState(db.feedItems[0]?.id || '');
  const [feedFlockId, setFeedFlockId] = useState(db.flocks[0]?.id || '');
  const [feedQuantityKg, setFeedQuantityKg] = useState(30);

  // New Flock State
  const [flockName, setFlockName] = useState('');
  const [flockSpecies, setFlockSpecies] = useState<PoultrySpecies>('Mikolongwe chicken');
  const [flockBreed, setFlockBreed] = useState('Mikolongwe Line');
  const [flockInitialQty, setFlockInitialQty] = useState(200);
  const [flockShed, setFlockShed] = useState('Shed 1');
  const [flockAgeGroup, setFlockAgeGroup] = useState<AgeGroup>('chicks');

  // Customer Incubation State
  const [custName, setCustName] = useState('');
  const [custPhone, setCustPhone] = useState('+265 ');
  const [custVillage, setCustVillage] = useState('Liwonde');
  const [custSpecies, setCustSpecies] = useState<PoultrySpecies>('Local chicken');
  const [custEggsReceived, setCustEggsReceived] = useState(100);
  const [custEggsAccepted, setCustEggsAccepted] = useState(95);
  const [custEggsRejected, setCustEggsRejected] = useState(5);
  const [custRejectionReason, setCustRejectionReason] = useState('Thin shells / dirty');
  const [custIncubator, setCustIncubator] = useState('Incubator 1 (Digital Solar 500)');
  const [custRatePerEgg, setCustRatePerEgg] = useState(250);
  const [custDeposit, setCustDeposit] = useState(15000);
  const [custDepositMethod, setCustDepositMethod] = useState<PaymentMethod>('airtel_money');

  // Sale State
  const [saleCustomerName, setSaleCustomerName] = useState('');
  const [saleProductCategory, setSaleProductCategory] = useState<ProductCategory>('fertilized_eggs');
  const [saleQty, setSaleQty] = useState(5);
  const [saleUnit, setSaleUnit] = useState<any>('trays');
  const [saleUnitPrice, setSaleUnitPrice] = useState(8500);
  const [salePaymentMethod, setSalePaymentMethod] = useState<PaymentMethod>('cash');
  const [salePaid, setSalePaid] = useState(42500);

  // Expense State
  const [expCategory, setExpCategory] = useState<'feed' | 'electricity' | 'labour_salaries' | 'packaging' | 'other'>('feed');
  const [expDesc, setExpDesc] = useState('');
  const [expAmount, setExpAmount] = useState(35000);
  const [expPayee, setExpPayee] = useState('');
  const [expPaymentMethod, setExpPaymentMethod] = useState<PaymentMethod>('cash');

  // Task State
  const [taskTitle, setTaskTitle] = useState('');
  const [taskCategory, setTaskCategory] = useState<TaskCategory>('cleaning');
  const [taskPriority, setTaskPriority] = useState<'high' | 'medium' | 'low'>('medium');
  const [taskDueDate, setTaskDueDate] = useState(today);

  const showSuccess = (msg: string) => {
    setSuccessMessage(msg);
    setTimeout(() => {
      setSuccessMessage(null);
      onClose();
    }, 1200);
  };

  const handleEggSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const flock = db.flocks.find((f) => f.id === eggFlockId);
    if (!flock) return;
    addEggCollection({
      collectionDate: today,
      collectionTime: new Date().toTimeString().substring(0, 5),
      flockId: flock.id,
      flockName: flock.name,
      species: flock.species,
      breed: flock.breed,
      totalCollected: Number(eggTotal),
      saleableCount: Number(eggSaleable),
      crackedCount: Number(eggCracked),
      dirtyCount: Number(eggDirty),
      damagedRejectedCount: Number(eggDamaged),
      farmUsedCount: Number(eggFarmUsed),
      reservedIncubationCount: Number(eggReservedInc),
      recordedBy: currentUser.name,
      notes: 'Logged via quick operations',
    });
    showSuccess('Egg collection recorded successfully!');
  };

  const handleMortalitySubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const flock = db.flocks.find((f) => f.id === mortFlockId);
    if (!flock) return;
    addMortalityRecord({
      date: today,
      flockId: flock.id,
      flockName: flock.name,
      numberOfBirds: Number(mortCount),
      ageGroup: flock.ageGroup,
      suspectedCause: mortCause || 'Unknown',
      observedSymptoms: mortSymptoms,
      actionTaken: mortAction,
      recordedBy: currentUser.name,
    });
    showSuccess(`Mortality of ${mortCount} birds recorded & flock count updated.`);
  };

  const handleFeedSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const item = db.feedItems.find((f) => f.id === feedItemId);
    const flock = db.flocks.find((f) => f.id === feedFlockId);
    if (!item || !flock) return;
    addFeedUsage({
      date: today,
      flockId: flock.id,
      flockName: flock.name,
      feedItemId: item.id,
      productName: item.productName,
      quantityUsedKg: Number(feedQuantityKg),
      recordedBy: currentUser.name,
    });
    showSuccess(`Logged ${feedQuantityKg}kg feed use. Stock updated.`);
  };

  const handleFlockSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!flockName) return;
    const flockCode = `FLK-2026-${String(db.flocks.length + 1).padStart(3, '0')}`;
    addFlock({
      flockCode,
      name: flockName,
      species: flockSpecies,
      breed: flockBreed || flockSpecies,
      dateAcquired: today,
      sourceSupplier: 'Local / Farm Hatchery',
      initialQuantity: Number(flockInitialQty),
      housingShed: flockShed || 'Shed 1',
      purpose: flockSpecies === 'Local chicken' ? 'breeding' : 'eggs',
      ageGroup: flockAgeGroup,
      status: 'active',
      notes: 'New flock registered',
    });
    showSuccess(`Flock ${flockName} registered.`);
  };

  const handleCustomerIncubationSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!custName || !custPhone) return;

    const durationDays = settings.speciesIncubationDays[custSpecies] || 21;
    const dateSetObj = new Date(today);
    const hatchDateObj = new Date(dateSetObj.getTime() + durationDays * 86400000);
    const collectDateObj = new Date(dateSetObj.getTime() + (durationDays + 1) * 86400000);
    const expectedHatchDate = hatchDateObj.toISOString().split('T')[0];
    const plannedCollectionDate = collectDateObj.toISOString().split('T')[0];

    const totalCharge = custEggsAccepted * custRatePerEgg;
    const batchCode = `CUST-INC-${String(db.customerIncubations.length + 43).padStart(3, '0')}`;

    addCustomerIncubationBatch(
      {
        batchCode,
        customerId: `cust_${Date.now()}`,
        customerName: custName,
        customerPhone: custPhone,
        customerVillage: custVillage,
        species: custSpecies,
        dateReceived: today,
        dateSet: today,
        eggsReceived: Number(custEggsReceived),
        eggsAccepted: Number(custEggsAccepted),
        eggsRejectedAtIntake: Number(custEggsRejected),
        rejectionReason: custRejectionReason,
        incubatorId: custIncubator,
        durationDays,
        expectedHatchDate,
        plannedCollectionDate,
        pricingMethod: 'per_egg',
        unitRate: custRatePerEgg,
        totalCharge,
        deposit: Number(custDeposit),
        status: 'incubating',
        recordedBy: currentUser.name,
      },
      Number(custDeposit),
      custDepositMethod
    );

    showSuccess(`Batch ${batchCode} created for ${custName}.`);
  };

  const handleSaleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const total = saleQty * saleUnitPrice;
    const invoiceNumber = `INV-2026-${String(db.sales.length + 83).padStart(3, '0')}`;
    addSale({
      invoiceNumber,
      date: today,
      customerName: saleCustomerName || 'Walk-in Customer',
      productCategory: saleProductCategory,
      description: `${saleQty} ${saleUnit} of ${saleProductCategory}`,
      quantity: Number(saleQty),
      unit: saleUnit,
      unitPrice: Number(saleUnitPrice),
      discount: 0,
      total,
      paymentReceived: Number(salePaid),
      paymentMethod: salePaymentMethod,
      deliveryStatus: 'collected',
      salesperson: currentUser.name,
    });
    showSuccess(`Sale of MK ${total} recorded.`);
  };

  const handleExpenseSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const expenseNumber = `EXP-2026-${String(db.expenses.length + 55).padStart(3, '0')}`;
    addExpense({
      expenseNumber,
      date: today,
      category: expCategory,
      description: expDesc || `${expCategory} expenditure`,
      amount: Number(expAmount),
      paymentMethod: expPaymentMethod,
      recipientOrPayee: expPayee || 'Liwonde Merchant',
      recordedBy: currentUser.name,
    });
    showSuccess(`Expense of MK ${expAmount} recorded.`);
  };

  const handleTaskSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!taskTitle) return;
    addTask({
      title: taskTitle,
      category: taskCategory,
      dueDate: taskDueDate,
      assignedPerson: currentUser.name,
      priority: taskPriority,
      status: 'pending',
    });
    showSuccess(`Task "${taskTitle}" scheduled.`);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
      <div className="w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 my-auto max-h-[92vh] flex flex-col">
        {/* Modal Header */}
        <div className="bg-emerald-950 text-white px-4 py-3 flex items-center justify-between shrink-0">
          <div>
            <h3 className="text-sm font-bold tracking-tight">Quick Farm Action</h3>
            <p className="text-[11px] text-emerald-300">Fast record entry for daily farm operations</p>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-lg text-emerald-300 hover:text-white hover:bg-emerald-900 transition"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Quick Ops Selector Pills */}
        <div className="px-3 pt-3 pb-2 border-b border-slate-200 bg-slate-50 flex gap-1.5 overflow-x-auto shrink-0">
          <button
            onClick={() => setActiveType('egg_collection')}
            className={`px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition ${
              activeType === 'egg_collection'
                ? 'bg-emerald-800 text-white'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
            }`}
          >
            <Egg className="w-3.5 h-3.5" />
            <span>Eggs</span>
          </button>
          <button
            onClick={() => setActiveType('mortality')}
            className={`px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition ${
              activeType === 'mortality'
                ? 'bg-rose-800 text-white'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
            }`}
          >
            <Skull className="w-3.5 h-3.5" />
            <span>Mortality</span>
          </button>
          <button
            onClick={() => setActiveType('feed_use')}
            className={`px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition ${
              activeType === 'feed_use'
                ? 'bg-amber-800 text-white'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
            }`}
          >
            <Wheat className="w-3.5 h-3.5" />
            <span>Feed Use</span>
          </button>
          <button
            onClick={() => setActiveType('customer_incubation')}
            className={`px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition ${
              activeType === 'customer_incubation'
                ? 'bg-cyan-800 text-white'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
            }`}
          >
            <UserPlus className="w-3.5 h-3.5" />
            <span>Cust Incubation</span>
          </button>
          <button
            onClick={() => setActiveType('sale')}
            className={`px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition ${
              activeType === 'sale'
                ? 'bg-emerald-700 text-white'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
            }`}
          >
            <ShoppingBag className="w-3.5 h-3.5" />
            <span>Sale</span>
          </button>
          <button
            onClick={() => setActiveType('expense')}
            className={`px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition ${
              activeType === 'expense'
                ? 'bg-slate-800 text-white'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
            }`}
          >
            <DollarSign className="w-3.5 h-3.5" />
            <span>Expense</span>
          </button>
          <button
            onClick={() => setActiveType('task')}
            className={`px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition ${
              activeType === 'task'
                ? 'bg-purple-800 text-white'
                : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
            }`}
          >
            <CheckSquare className="w-3.5 h-3.5" />
            <span>Task</span>
          </button>
        </div>

        {/* Success feedback toast */}
        {successMessage && (
          <div className="bg-emerald-600 text-white p-3 text-center text-xs font-bold animate-in fade-in">
            {successMessage}
          </div>
        )}

        {/* Scrollable Form Body */}
        <div className="p-4 overflow-y-auto space-y-4 text-xs">
          {/* 1. Egg Collection Form */}
          {activeType === 'egg_collection' && (
            <form onSubmit={handleEggSubmit} className="space-y-3">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Select Flock</label>
                <select
                  value={eggFlockId}
                  onChange={(e) => setEggFlockId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                >
                  {db.flocks.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.name} ({f.species} • {f.currentQuantity} birds)
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Total Collected</label>
                  <input
                    type="number"
                    value={eggTotal}
                    onChange={(e) => {
                      const tot = Number(e.target.value);
                      setEggTotal(tot);
                      // Auto estimate saleable
                      setEggSaleable(Math.max(0, tot - eggCracked - eggDirty - eggDamaged));
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg text-sm font-bold"
                    required
                  />
                  <span className="text-[10px] text-slate-500">
                    ≈ {(eggTotal / 30).toFixed(1)} trays (30 eggs/tray)
                  </span>
                </div>
                <div>
                  <label className="block font-semibold text-emerald-800 mb-1">Saleable Eggs</label>
                  <input
                    type="number"
                    value={eggSaleable}
                    onChange={(e) => setEggSaleable(Number(e.target.value))}
                    className="w-full p-2 border border-emerald-300 rounded-lg text-sm font-bold text-emerald-900 bg-emerald-50/50"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                <div>
                  <label className="block text-[11px] font-medium text-slate-600 mb-0.5">Cracked</label>
                  <input
                    type="number"
                    value={eggCracked}
                    onChange={(e) => setEggCracked(Number(e.target.value))}
                    className="w-full p-1.5 border border-slate-300 rounded bg-white"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-medium text-slate-600 mb-0.5">Dirty</label>
                  <input
                    type="number"
                    value={eggDirty}
                    onChange={(e) => setEggDirty(Number(e.target.value))}
                    className="w-full p-1.5 border border-slate-300 rounded bg-white"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-medium text-rose-700 mb-0.5">Damaged/Reject</label>
                  <input
                    type="number"
                    value={eggDamaged}
                    onChange={(e) => setEggDamaged(Number(e.target.value))}
                    className="w-full p-1.5 border border-rose-300 rounded bg-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[11px] font-medium text-slate-700 mb-0.5">Farm Used / Kitchen</label>
                  <input
                    type="number"
                    value={eggFarmUsed}
                    onChange={(e) => setEggFarmUsed(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-medium text-slate-700 mb-0.5">Set Aside for Incubation</label>
                  <input
                    type="number"
                    value={eggReservedInc}
                    onChange={(e) => setEggReservedInc(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  />
                </div>
              </div>

              <button
                type="submit"
                className="w-full py-2.5 bg-emerald-800 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition"
              >
                Save Egg Collection
              </button>
            </form>
          )}

          {/* 2. Mortality Form */}
          {activeType === 'mortality' && (
            <form onSubmit={handleMortalitySubmit} className="space-y-3">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Select Affected Flock</label>
                <select
                  value={mortFlockId}
                  onChange={(e) => setMortFlockId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                >
                  {db.flocks.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.name} ({f.currentQuantity} live birds remaining)
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-rose-700 mb-1">Number of Dead Birds</label>
                  <input
                    type="number"
                    min="1"
                    value={mortCount}
                    onChange={(e) => setMortCount(Number(e.target.value))}
                    className="w-full p-2 border border-rose-300 rounded-lg font-bold text-rose-900"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Suspected Cause</label>
                  <select
                    value={mortCause}
                    onChange={(e) => setMortCause(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="Unknown">Unknown / Under observation</option>
                    <option value="Heat stress">Heat stress</option>
                    <option value="Predator / Snake attack">Predator / Snake attack</option>
                    <option value="Respiratory difficulty">Respiratory symptoms</option>
                    <option value="Smothering / Congestion">Smothering / Trampling</option>
                    <option value="Culling (severe injury)">Culling (severe injury)</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Symptoms Observed (Optional)</label>
                <input
                  type="text"
                  placeholder="e.g. Panting, weakness, no external lesion"
                  value={mortSymptoms}
                  onChange={(e) => setMortSymptoms(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                />
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Action Taken</label>
                <input
                  type="text"
                  value={mortAction}
                  onChange={(e) => setMortAction(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                />
              </div>

              <p className="text-[11px] text-slate-500 italic">
                Flock bird count will be automatically reduced and logged to movement audit.
              </p>

              <button
                type="submit"
                className="w-full py-2.5 bg-rose-800 hover:bg-rose-700 text-white font-bold rounded-xl shadow-xs transition"
              >
                Record Mortality & Update Flock
              </button>
            </form>
          )}

          {/* 3. Feed Use Form */}
          {activeType === 'feed_use' && (
            <form onSubmit={handleFeedSubmit} className="space-y-3">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Select Feed Item</label>
                <select
                  value={feedItemId}
                  onChange={(e) => setFeedItemId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                >
                  {db.feedItems.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.productName} (In Stock: {f.currentStockKg} kg)
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Fed to Flock</label>
                <select
                  value={feedFlockId}
                  onChange={(e) => setFeedFlockId(e.target.value)}
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
                <label className="block font-semibold text-slate-700 mb-1">Quantity Used (kg)</label>
                <input
                  type="number"
                  min="0.5"
                  step="0.5"
                  value={feedQuantityKg}
                  onChange={(e) => setFeedQuantityKg(Number(e.target.value))}
                  className="w-full p-2 border border-slate-300 rounded-lg text-sm font-bold"
                  required
                />
                <span className="text-[10px] text-slate-500">
                  Will be automatically deducted from inventory balance.
                </span>
              </div>

              <button
                type="submit"
                className="w-full py-2.5 bg-amber-800 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition"
              >
                Log Feed Consumption
              </button>
            </form>
          )}

          {/* 4. Customer Incubation Form */}
          {activeType === 'customer_incubation' && (
            <form onSubmit={handleCustomerIncubationSubmit} className="space-y-3">
              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block font-semibold text-slate-700 mb-0.5">Customer Name</label>
                  <input
                    type="text"
                    placeholder="e.g. Mary Banda"
                    value={custName}
                    onChange={(e) => setCustName(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-0.5">Phone Number</label>
                  <input
                    type="tel"
                    placeholder="+265 99..."
                    value={custPhone}
                    onChange={(e) => setCustPhone(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block text-[11px] font-medium text-slate-700 mb-0.5">Village / Town</label>
                  <input
                    type="text"
                    value={custVillage}
                    onChange={(e) => setCustVillage(e.target.value)}
                    className="w-full p-1.5 border border-slate-300 rounded-lg bg-white"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-medium text-slate-700 mb-0.5">Species</label>
                  <select
                    value={custSpecies}
                    onChange={(e) => setCustSpecies(e.target.value as PoultrySpecies)}
                    className="w-full p-1.5 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="Local chicken">Local chicken (21 days)</option>
                    <option value="Mikolongwe chicken">Mikolongwe (21 days)</option>
                    <option value="Sasso chicken">Sasso (21 days)</option>
                    <option value="Guinea fowl">Guinea fowl (Nkhanga - 28 days)</option>
                    <option value="Turkey">Turkey (28 days)</option>
                    <option value="Duck">Duck (28 days)</option>
                    <option value="Quail">Quail (17 days)</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                <div>
                  <label className="block text-[10px] font-medium text-slate-600">Eggs Received</label>
                  <input
                    type="number"
                    value={custEggsReceived}
                    onChange={(e) => {
                      const rec = Number(e.target.value);
                      setCustEggsReceived(rec);
                      setCustEggsAccepted(Math.max(0, rec - custEggsRejected));
                    }}
                    className="w-full p-1.5 border border-slate-300 rounded bg-white font-bold"
                    required
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold text-emerald-800">Accepted</label>
                  <input
                    type="number"
                    value={custEggsAccepted}
                    onChange={(e) => setCustEggsAccepted(Number(e.target.value))}
                    className="w-full p-1.5 border border-emerald-400 rounded bg-white font-bold text-emerald-900"
                    required
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-medium text-rose-700">Rejected</label>
                  <input
                    type="number"
                    value={custEggsRejected}
                    onChange={(e) => {
                      const rej = Number(e.target.value);
                      setCustEggsRejected(rej);
                      setCustEggsAccepted(Math.max(0, custEggsReceived - rej));
                    }}
                    className="w-full p-1.5 border border-rose-300 rounded bg-white font-bold text-rose-900"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block text-[11px] font-medium text-slate-700 mb-0.5">Fee per Egg (MK)</label>
                  <input
                    type="number"
                    value={custRatePerEgg}
                    onChange={(e) => setCustRatePerEgg(Number(e.target.value))}
                    className="w-full p-1.5 border border-slate-300 rounded-lg bg-white"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-bold text-slate-900 mb-0.5">
                    Total Charge: MK {(custEggsAccepted * custRatePerEgg).toLocaleString()}
                  </label>
                  <div className="text-[10px] text-slate-500">Auto calculated</div>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-2 bg-emerald-50/60 p-2.5 rounded-lg border border-emerald-200">
                <div>
                  <label className="block text-[11px] font-semibold text-emerald-950 mb-0.5">Initial Deposit (MK)</label>
                  <input
                    type="number"
                    value={custDeposit}
                    onChange={(e) => setCustDeposit(Number(e.target.value))}
                    className="w-full p-1.5 border border-emerald-300 rounded-lg bg-white font-bold"
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-medium text-emerald-950 mb-0.5">Payment Method</label>
                  <select
                    value={custDepositMethod}
                    onChange={(e) => setCustDepositMethod(e.target.value as PaymentMethod)}
                    className="w-full p-1.5 border border-emerald-300 rounded-lg bg-white"
                  >
                    <option value="airtel_money">Airtel Money</option>
                    <option value="tnm_mpamba">TNM Mpamba</option>
                    <option value="cash">Cash (Off-line)</option>
                    <option value="bank_transfer">Bank Transfer</option>
                  </select>
                </div>
              </div>

              <button
                type="submit"
                className="w-full py-2.5 bg-cyan-900 hover:bg-cyan-800 text-white font-bold rounded-xl shadow-xs transition"
              >
                Register & Set Customer Batch
              </button>
            </form>
          )}

          {/* 5. Sale Form */}
          {activeType === 'sale' && (
            <form onSubmit={handleSaleSubmit} className="space-y-3">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Customer Name / Buyer</label>
                <input
                  type="text"
                  placeholder="e.g. Shire River Safari Lodge or Cash Customer"
                  value={saleCustomerName}
                  onChange={(e) => setSaleCustomerName(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Product</label>
                  <select
                    value={saleProductCategory}
                    onChange={(e) => {
                      const cat = e.target.value as ProductCategory;
                      setSaleProductCategory(cat);
                      if (cat === 'fertilized_eggs') {
                        setSaleUnit('trays');
                        setSaleUnitPrice(8500);
                        setSalePaid(saleQty * 8500);
                      } else if (cat === 'damaged_eggs') {
                        setSaleUnit('trays');
                        setSaleUnitPrice(2500);
                        setSalePaid(saleQty * 2500);
                      } else if (cat === 'infertile_eggs') {
                        setSaleUnit('trays');
                        setSaleUnitPrice(3000);
                        setSalePaid(saleQty * 3000);
                      } else if (cat === 'quails') {
                        setSaleUnit('birds');
                        setSaleUnitPrice(4000);
                        setSalePaid(saleQty * 4000);
                      } else if (cat === 'ducklings') {
                        setSaleUnit('birds');
                        setSaleUnitPrice(10000);
                        setSalePaid(saleQty * 10000);
                      } else if (cat === 'ducks') {
                        setSaleUnit('birds');
                        setSaleUnitPrice(25000);
                        setSalePaid(saleQty * 25000);
                      } else if (cat === 'day_old_chicks') {
                        setSaleUnit('birds');
                        setSaleUnitPrice(3000);
                        setSalePaid(saleQty * 3000);
                      } else {
                        setSaleUnit('birds');
                        setSaleUnitPrice(8500);
                        setSalePaid(saleQty * 8500);
                      }
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="fertilized_eggs">🥚 Fertilized Eggs (Hatching)</option>
                    <option value="damaged_eggs">🍳 Damaged Eggs (Bakery / Cooking)</option>
                    <option value="infertile_eggs">🔍 Infertile Eggs (Candled Clears)</option>
                    <option value="quails">🐦 Quals (Live Quails)</option>
                    <option value="ducklings">🦆 Ducklings (Day-Old)</option>
                    <option value="ducks">🦆 Ducks (Mature / Table)</option>
                    <option value="live_birds">🐔 Live Chickens (Mikolongwe / Sasso)</option>
                    <option value="day_old_chicks">🐣 Day-Old Chicks</option>
                    <option value="turkeys">🦃 Turkeys / Poults</option>
                    <option value="guinea_fowl">🪶 Guinea Fowl (Nkhanga)</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Quantity & Unit</label>
                  <div className="flex gap-1.5">
                    <input
                      type="number"
                      min="1"
                      value={saleQty}
                      onChange={(e) => {
                        const q = Number(e.target.value);
                        setSaleQty(q);
                        setSalePaid(q * saleUnitPrice);
                      }}
                      className="w-20 p-2 border border-slate-300 rounded-lg text-sm font-bold"
                      required
                    />
                    <select
                      value={saleUnit}
                      onChange={(e) => setSaleUnit(e.target.value as any)}
                      className="flex-1 p-2 border border-slate-300 rounded-lg bg-white text-xs"
                    >
                      <option value="trays">Trays</option>
                      <option value="individual_eggs">Individual Eggs</option>
                      <option value="birds">Birds</option>
                    </select>
                  </div>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Unit Price (MK)</label>
                  <input
                    type="number"
                    value={saleUnitPrice}
                    onChange={(e) => {
                      const p = Number(e.target.value);
                      setSaleUnitPrice(p);
                      setSalePaid(saleQty * p);
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-900 mb-1">
                    Total: MK {(saleQty * saleUnitPrice).toLocaleString()}
                  </label>
                  <div className="text-[11px] text-slate-500">Amount to collect</div>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Payment Received (MK)</label>
                  <input
                    type="number"
                    value={salePaid}
                    onChange={(e) => setSalePaid(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Payment Method</label>
                  <select
                    value={salePaymentMethod}
                    onChange={(e) => setSalePaymentMethod(e.target.value as PaymentMethod)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="cash">Cash</option>
                    <option value="airtel_money">Airtel Money</option>
                    <option value="tnm_mpamba">TNM Mpamba</option>
                    <option value="bank_transfer">Bank Transfer</option>
                  </select>
                </div>
              </div>

              <button
                type="submit"
                className="w-full py-2.5 bg-emerald-700 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-xs transition"
              >
                Record Sale & Create Invoice
              </button>
            </form>
          )}

          {/* 6. Expense Form */}
          {activeType === 'expense' && (
            <form onSubmit={handleExpenseSubmit} className="space-y-3">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Expense Category</label>
                <select
                  value={expCategory}
                  onChange={(e) => setExpCategory(e.target.value as any)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                >
                  <option value="feed">Feed purchase</option>
                  <option value="electricity">Electricity / ESCOM tokens</option>
                  <option value="labour_salaries">Farm labour / Salaries</option>
                  <option value="packaging">Packaging (Trays / Cartons)</option>
                  <option value="transport_fuel">Transport & Fuel</option>
                  <option value="vaccines_medicines">Medicines & Vaccines</option>
                  <option value="equipment_repairs">Equipment / Repairs</option>
                  <option value="other">Other overheads</option>
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Description</label>
                <input
                  type="text"
                  placeholder="e.g. 5 bags layer mash, ESCOM units, etc."
                  value={expDesc}
                  onChange={(e) => setExpDesc(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-rose-800 mb-1">Amount in MK</label>
                  <input
                    type="number"
                    value={expAmount}
                    onChange={(e) => setExpAmount(Number(e.target.value))}
                    className="w-full p-2 border border-rose-300 rounded-lg font-bold text-rose-900"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Paid To / Recipient</label>
                  <input
                    type="text"
                    placeholder="Merchant or payee name"
                    value={expPayee}
                    onChange={(e) => setExpPayee(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Payment Method</label>
                <select
                  value={expPaymentMethod}
                  onChange={(e) => setExpPaymentMethod(e.target.value as PaymentMethod)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                >
                  <option value="cash">Cash</option>
                  <option value="airtel_money">Airtel Money</option>
                  <option value="tnm_mpamba">TNM Mpamba</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>

              <button
                type="submit"
                className="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs transition"
              >
                Save Expense in Cashbook
              </button>
            </form>
          )}

          {/* 7. Task Form */}
          {activeType === 'task' && (
            <form onSubmit={handleTaskSubmit} className="space-y-3">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Task Title</label>
                <input
                  type="text"
                  placeholder="e.g. Inspect brooder heaters, turn eggs in Incubator 2"
                  value={taskTitle}
                  onChange={(e) => setTaskTitle(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Category</label>
                  <select
                    value={taskCategory}
                    onChange={(e) => setTaskCategory(e.target.value as TaskCategory)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="egg_collection">Egg Collection</option>
                    <option value="feeding">Feeding</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="disinfection">Disinfection</option>
                    <option value="vaccination">Vaccination</option>
                    <option value="medication">Medication</option>
                    <option value="candling">Candling</option>
                    <option value="incubator_transfer">Lockdown / Transfer</option>
                    <option value="hatch_monitoring">Hatch Monitoring</option>
                    <option value="customer_collection">Customer Collection</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Priority</label>
                  <select
                    value={taskPriority}
                    onChange={(e) => setTaskPriority(e.target.value as any)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Due Date</label>
                <input
                  type="date"
                  value={taskDueDate}
                  onChange={(e) => setTaskDueDate(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                />
              </div>

              <button
                type="submit"
                className="w-full py-2.5 bg-purple-900 hover:bg-purple-800 text-white font-bold rounded-xl shadow-xs transition"
              >
                Schedule Task
              </button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
};
