import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { FarmIncubationBatch, CustomerIncubationBatch, PoultrySpecies, IncubationStage, PaymentRecord, PaymentMethod } from '../../types';
import { formatDate, formatMWK } from '../../utils/i18n';
import { ReceiptModal } from '../common/ReceiptModal';
import { WhatsAppAlertModal, WhatsAppAlertType } from './WhatsAppAlertModal';
import { exportMonthlyFarmExcel } from '../../services/excelExport';
import {
  Flame,
  Plus,
  Calendar,
  CheckCircle,
  AlertCircle,
  Users,
  Eye,
  ArrowRight,
  Printer,
  Share2,
  DollarSign,
  ChevronRight,
  ShieldCheck,
  Search,
  Phone,
  FileSpreadsheet,
  MessageCircle,
} from 'lucide-react';

export const IncubationView: React.FC = () => {
  const {
    db,
    settings,
    currentUser,
    metrics,
    addFarmIncubationBatch,
    updateFarmIncubationBatch,
    addCustomerIncubationBatch,
    addCustomerPayment,
    updateCustomerIncubationBatch,
    canEditStocks,
    isViewerOnly,
  } = useFarmData();

  const [activeSubTab, setActiveSubTab] = useState<'customer' | 'farm'>('customer');
  const [selectedReceiptBatch, setSelectedReceiptBatch] = useState<CustomerIncubationBatch | null>(null);
  const [selectedWhatsAppBatch, setSelectedWhatsAppBatch] = useState<{
    batch: CustomerIncubationBatch;
    alertType: WhatsAppAlertType;
  } | null>(null);
  const [selectedExcelMonth, setSelectedExcelMonth] = useState(
    new Date().toISOString().substring(0, 7)
  );
  const [search, setSearch] = useState('');

  // Modals
  const [showAddFarmModal, setShowAddFarmModal] = useState(false);
  const [showAddCustomerModal, setShowAddCustomerModal] = useState(false);
  const [selectedBatchForCandling, setSelectedBatchForCandling] = useState<FarmIncubationBatch | null>(null);
  const [selectedBatchForHatch, setSelectedBatchForHatch] = useState<FarmIncubationBatch | null>(null);
  const [selectedCustBatchForPayment, setSelectedCustBatchForPayment] = useState<CustomerIncubationBatch | null>(null);
  const [selectedCustBatchForHatch, setSelectedCustBatchForHatch] = useState<CustomerIncubationBatch | null>(null);

  // Farm Batch Form State
  const today = new Date().toISOString().split('T')[0];
  const [farmSpecies, setFarmSpecies] = useState<PoultrySpecies>('Mikolongwe chicken');
  const [farmFlockId, setFarmFlockId] = useState(db.flocks[0]?.id || '');
  const [farmEggsSet, setFarmEggsSet] = useState(150);
  const [farmSettingDate, setFarmSettingDate] = useState(today);
  const [farmExpectedHatchDate, setFarmExpectedHatchDate] = useState(() => {
    const d = new Date(today);
    d.setDate(d.getDate() + 21);
    return d.toISOString().split('T')[0];
  });
  const [farmIncubatorId, setFarmIncubatorId] = useState('Incubator 1 (Digital Solar 500)');
  const [farmNotes, setFarmNotes] = useState('');

  // Customer Batch Form State
  const [custName, setCustName] = useState('');
  const [custPhone, setCustPhone] = useState('+265 ');
  const [custVillage, setCustVillage] = useState('Liwonde');
  const [custSpecies, setCustSpecies] = useState<PoultrySpecies>('Local chicken');
  const [custBreed, setCustBreed] = useState('Local Indigenous');
  const [custEggsReceived, setCustEggsReceived] = useState(120);
  const [custEggsAccepted, setCustEggsAccepted] = useState(110);
  const [custEggsRejected, setCustEggsRejected] = useState(10);
  const [custRejectionReason, setCustRejectionReason] = useState('Cracked during transport / dirty');
  const [custSettingDate, setCustSettingDate] = useState(today);
  const [custExpectedHatchDate, setCustExpectedHatchDate] = useState(() => {
    const d = new Date(today);
    d.setDate(d.getDate() + 21);
    return d.toISOString().split('T')[0];
  });
  const [custIncubatorId, setCustIncubatorId] = useState('Incubator 1 (Digital Solar 500)');
  const [custPricingMethod, setCustPricingMethod] = useState<'per_egg' | 'per_chick' | 'flat_fee'>('per_egg');
  const [custUnitRate, setCustUnitRate] = useState(250);
  const [custDeposit, setCustDeposit] = useState(15000);
  const [custDepositMethod, setCustDepositMethod] = useState<PaymentMethod>('airtel_money');
  const [custNotes, setCustNotes] = useState('');

  // Candling Form State
  const [candlingFertile, setCandlingFertile] = useState(130);
  const [candlingInfertile, setCandlingInfertile] = useState(20);

  // Farm Hatch Form State
  const [farmHatchDate, setFarmHatchDate] = useState(today);
  const [hatchChicks, setHatchChicks] = useState(120);
  const [hatchUnhatched, setHatchUnhatched] = useState(10);
  const [hatchQualityNotes, setHatchQualityNotes] = useState('Vigorous chicks, active and dry.');

  // Customer Payment Form State
  const [payAmount, setPayAmount] = useState(10000);
  const [payMethod, setPayMethod] = useState<PaymentMethod>('cash');
  const [payReceiptNo, setPayReceiptNo] = useState(`REC-${Math.floor(1000 + Math.random() * 9000)}`);
  const [payNotes, setPayNotes] = useState('');

  // Customer Hatch & Delivery State
  const [custHatchDate, setCustHatchDate] = useState(today);
  const [custHatchedChicks, setCustHatchedChicks] = useState(90);
  const [custDeliveredChicks, setCustDeliveredChicks] = useState(90);
  const [custUnhatchedCount, setCustUnhatchedCount] = useState(20);
  const [custCollectedCheck, setCustCollectedCheck] = useState(true);

  // Helper for computing hatch date
  const computeHatchDate = (setDateStr: string, sp: PoultrySpecies) => {
    const duration = settings.speciesIncubationDays[sp] || (sp === 'Guinea fowl' ? 28 : sp === 'Duck' || sp === 'Turkey' ? 28 : sp === 'Quail' ? 17 : 21);
    const d = new Date(setDateStr);
    d.setDate(d.getDate() + duration);
    return d.toISOString().split('T')[0];
  };

  // Calculation helpers
  const handleCreateFarmBatch = (e: React.FormEvent) => {
    e.preventDefault();
    const durationDays = settings.speciesIncubationDays[farmSpecies] || (farmSpecies === 'Guinea fowl' ? 28 : 21);
    const dateSetObj = new Date(farmSettingDate);
    const firstCandleObj = new Date(dateSetObj.getTime() + 7 * 86400000);
    const lockdownObj = new Date(dateSetObj.getTime() + (durationDays - 3) * 86400000);

    const flock = db.flocks.find((f) => f.id === farmFlockId);
    const batchCode = `INC-2026-${String(db.farmIncubations.length + 1).padStart(3, '0')}`;

    addFarmIncubationBatch({
      batchCode,
      species: farmSpecies,
      breedSource: flock?.name || farmSpecies,
      flockId: farmFlockId,
      eggSettingDate: farmSettingDate,
      eggsSet: Number(farmEggsSet),
      incubatorId: farmIncubatorId,
      expectedHatchDate: farmExpectedHatchDate,
      incubationDurationDays: durationDays,
      firstCandlingDate: firstCandleObj.toISOString().split('T')[0],
      lockdownDate: lockdownObj.toISOString().split('T')[0],
      status: 'incubating',
      notes: farmNotes,
    });

    setShowAddFarmModal(false);
  };

  const handleCreateCustomerBatch = (e: React.FormEvent) => {
    e.preventDefault();
    if (!custName || !custPhone) return;

    const durationDays = settings.speciesIncubationDays[custSpecies] || (custSpecies === 'Guinea fowl' ? 28 : 21);
    const hatchDateObj = new Date(custExpectedHatchDate);
    const collectionDateObj = new Date(hatchDateObj.getTime() + 86400000);

    const totalCharge =
      custPricingMethod === 'per_egg'
        ? custEggsAccepted * custUnitRate
        : custPricingMethod === 'flat_fee'
        ? custUnitRate
        : custEggsAccepted * custUnitRate; // provisional if per chick

    const batchCode = `CUST-INC-${String(db.customerIncubations.length + 44).padStart(3, '0')}`;

    addCustomerIncubationBatch(
      {
        batchCode,
        customerId: `cust_${Date.now()}`,
        customerName: custName,
        customerPhone: custPhone,
        customerVillage: custVillage,
        species: custSpecies,
        breed: custBreed,
        dateReceived: custSettingDate,
        dateSet: custSettingDate,
        eggsReceived: Number(custEggsReceived),
        eggsAccepted: Number(custEggsAccepted),
        eggsRejectedAtIntake: Number(custEggsRejected),
        rejectionReason: custRejectionReason,
        incubatorId: custIncubatorId,
        durationDays,
        expectedHatchDate: custExpectedHatchDate,
        plannedCollectionDate: collectionDateObj.toISOString().split('T')[0],
        pricingMethod: custPricingMethod,
        unitRate: Number(custUnitRate),
        totalCharge,
        deposit: Number(custDeposit),
        status: 'incubating',
        notes: custNotes,
        recordedBy: currentUser.name,
      },
      Number(custDeposit),
      custDepositMethod
    );

    setShowAddCustomerModal(false);
  };

  const handleSaveCandling = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedBatchForCandling) return;
    updateFarmIncubationBatch(selectedBatchForCandling.id, {
      firstCandlingResult: {
        fertileCount: Number(candlingFertile),
        infertileCount: Number(candlingInfertile),
        dateRecorded: today,
      },
      status: 'candled',
    });
    setSelectedBatchForCandling(null);
  };

  const handleSaveFarmHatch = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedBatchForHatch) return;
    const hatched = Number(hatchChicks);
    const totalSet = selectedBatchForHatch.eggsSet;
    const hatchPercentage = totalSet > 0 ? (hatched / totalSet) * 100 : 0;

    updateFarmIncubationBatch(selectedBatchForHatch.id, {
      actualHatchDate: farmHatchDate,
      chicksHatched: hatched,
      unhatchedEggs: Number(hatchUnhatched),
      hatchPercentage: Number(hatchPercentage.toFixed(1)),
      chickQualityNotes: hatchQualityNotes,
      status: 'completed',
    });
    setSelectedBatchForHatch(null);
  };

  const handleSaveCustomerPayment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedCustBatchForPayment) return;
    addCustomerPayment(
      selectedCustBatchForPayment.id,
      Number(payAmount),
      payMethod,
      payReceiptNo,
      payNotes
    );
    setSelectedCustBatchForPayment(null);
  };

  const handleSaveCustomerHatch = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedCustBatchForHatch) return;
    const hatched = Number(custHatchedChicks);
    const delivered = Number(custDeliveredChicks);

    // If pricing was per chick hatched, update total charge
    let totalCharge = selectedCustBatchForHatch.totalCharge;
    if (selectedCustBatchForHatch.pricingMethod === 'per_chick') {
      totalCharge = hatched * selectedCustBatchForHatch.unitRate;
    }
    const balanceDue = Math.max(0, totalCharge - selectedCustBatchForHatch.amountPaid);

    updateCustomerIncubationBatch(selectedCustBatchForHatch.id, {
      chicksHatched: hatched,
      chicksDelivered: delivered,
      unhatchedEggs: Number(custUnhatchedCount),
      totalCharge,
      balanceDue,
      status: custCollectedCheck ? 'collected' : 'hatched',
      isCollected: custCollectedCheck,
      actualCollectionDate: custCollectedCheck ? custHatchDate : undefined,
    });
    setSelectedCustBatchForHatch(null);
  };

  // Filter lists
  const filteredCustomerBatches = db.customerIncubations.filter((b) => {
    return (
      b.customerName.toLowerCase().includes(search.toLowerCase()) ||
      b.batchCode.toLowerCase().includes(search.toLowerCase()) ||
      b.customerPhone.includes(search)
    );
  });

  const filteredFarmBatches = db.farmIncubations.filter((b) => {
    return (
      b.batchCode.toLowerCase().includes(search.toLowerCase()) ||
      b.species.toLowerCase().includes(search.toLowerCase()) ||
      b.incubatorId.toLowerCase().includes(search.toLowerCase())
    );
  });

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">
            Poultry Incubation Management
          </h2>
          <p className="text-xs text-slate-500">
            Solar-powered digital incubators in Liwonde • Farm-owned hatches & commercial customer incubation register.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <button
            onClick={() => exportMonthlyFarmExcel(db, selectedExcelMonth)}
            className="flex items-center gap-1.5 px-3 py-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-xl shadow-xs transition"
            title="Download Monthly Excel Spreadsheet report (.xlsx)"
          >
            <FileSpreadsheet className="w-4 h-4 text-emerald-700" />
            <span className="hidden sm:inline">Monthly Excel Report (.xlsx)</span>
            <span className="sm:hidden">Excel</span>
          </button>

          {canEditStocks ? (
            activeSubTab === 'customer' ? (
              <button
                onClick={() => setShowAddCustomerModal(true)}
                className="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
              >
                <Plus className="w-4 h-4" />
                <span>Register Customer Batch</span>
              </button>
            ) : (
              <button
                onClick={() => setShowAddFarmModal(true)}
                className="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
              >
                <Plus className="w-4 h-4" />
                <span>Set Farm Batch</span>
              </button>
            )
          ) : (
            <div className="px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-center gap-1.5">
              <Eye className="w-3.5 h-3.5 text-amber-600" />
              <span>Viewing Mode</span>
            </div>
          )}
        </div>
      </div>

      {/* Primary KPI Strip */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-slate-500">Total Eggs in Incubators</span>
          <div className="text-2xl font-black text-slate-900 mt-0.5">
            {metrics.totalIncubatingEggs.toLocaleString()}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            {metrics.customerEggsInIncubation} customer · {metrics.farmEggsInIncubators} farm
          </p>
        </div>

        <div className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-cyan-800">Customer Batches Active</span>
          <div className="text-2xl font-black text-cyan-950 mt-0.5">
            {db.customerIncubations.filter((b) => b.status !== 'collected').length}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            {db.customerIncubations.filter((b) => b.status === 'lockdown').length} entering lockdown
          </p>
        </div>

        <div className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-amber-800">Uncollected Customer Balances</span>
          <div className="text-xl font-black text-amber-900 mt-0.5">
            {formatMWK(
              db.customerIncubations.filter((b) => b.balanceDue > 0).reduce((sum, b) => sum + b.balanceDue, 0)
            )}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">To collect upon chick delivery</p>
        </div>

        <div className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-slate-500">Hatches Approaching</span>
          <div className="text-2xl font-black text-emerald-800 mt-0.5">
            {metrics.hatchesDueSoon.length}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">Due within 3 days</p>
        </div>
      </div>

      {/* Customer Eggs Ready for Hatch & Collection Desk */}
      {db.customerIncubations.filter((b) => !b.isCollected && (b.status === 'hatched' || b.status === 'lockdown' || new Date(b.expectedHatchDate).getTime() <= Date.now() + 3 * 86400000)).length > 0 && (
        <div className="bg-gradient-to-r from-emerald-900 to-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-md border border-emerald-700/60 space-y-3">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-emerald-800/80">
            <div className="flex items-center gap-2.5">
              <span className="w-3 h-3 rounded-full bg-amber-400 animate-ping shrink-0" />
              <div>
                <h3 className="text-sm font-bold tracking-tight text-white flex items-center gap-2">
                  <span>Customer Eggs Ready for Hatch & Chick Collection</span>
                  <span className="bg-amber-400 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-full uppercase">
                    WhatsApp Alerts
                  </span>
                </h3>
                <p className="text-xs text-emerald-200/90 mt-0.5">
                  Customers whose eggs have hatched or are due for collection today. Tap to notify via WhatsApp or call.
                </p>
              </div>
            </div>
            <span className="text-xs text-emerald-300 font-medium self-start sm:self-center">
              Liwonde Hatchery Alert Desk
            </span>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
            {db.customerIncubations
              .filter((b) => !b.isCollected && (b.status === 'hatched' || b.status === 'lockdown' || new Date(b.expectedHatchDate).getTime() <= Date.now() + 3 * 86400000))
              .map((batch) => (
                <div
                  key={batch.id}
                  className="bg-emerald-950/70 border border-emerald-800/80 rounded-xl p-3.5 flex flex-col justify-between gap-2.5 text-xs shadow-inner"
                >
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-white text-sm">{batch.customerName}</span>
                        <span className="text-[10px] font-mono text-emerald-300 bg-emerald-900 px-1.5 py-0.5 rounded">
                          {batch.batchCode}
                        </span>
                      </div>
                      <p className="text-emerald-200 text-xs mt-0.5">
                        {batch.species} · {batch.eggsAccepted} eggs · Due: <strong>{formatDate(batch.expectedHatchDate)}</strong>
                      </p>
                      <p className="text-[11px] text-emerald-300/80 mt-0.5">
                        Location: {batch.customerVillage || 'Liwonde'}
                      </p>
                    </div>

                    <div className="text-right shrink-0">
                      <span className="text-[10px] uppercase font-bold text-emerald-300 block">Balance Due</span>
                      <span className="text-sm font-black text-amber-300">
                        {formatMWK(batch.balanceDue)}
                      </span>
                    </div>
                  </div>

                  {/* Actions for this Customer */}
                  <div className="flex items-center gap-2 pt-2 border-t border-emerald-800/60">
                    <button
                      onClick={() => setSelectedWhatsAppBatch({ batch, alertType: 'hatching_collection' })}
                      className="flex-1 py-1.5 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition active:scale-95"
                    >
                      <MessageCircle className="w-3.5 h-3.5" />
                      <span>WhatsApp Hatch Alert</span>
                    </button>
                    <a
                      href={`tel:${batch.customerPhone}`}
                      className="py-1.5 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 text-emerald-100 font-semibold text-xs flex items-center gap-1 transition"
                      title="Call customer directly"
                    >
                      <Phone className="w-3.5 h-3.5 text-amber-400" />
                      <span>{batch.customerPhone}</span>
                    </a>
                  </div>
                </div>
              ))}
          </div>
        </div>
      )}

      {/* Tabs & Search Controls */}
      <div className="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div className="flex p-1 bg-slate-100 rounded-lg w-full sm:w-auto">
          <button
            onClick={() => setActiveSubTab('customer')}
            className={`flex-1 sm:flex-initial px-4 py-1.5 rounded-md text-xs font-semibold transition ${
              activeSubTab === 'customer'
                ? 'bg-white text-emerald-950 shadow-xs'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Commercial Customer Incubation ({db.customerIncubations.length})
          </button>
          <button
            onClick={() => setActiveSubTab('farm')}
            className={`flex-1 sm:flex-initial px-4 py-1.5 rounded-md text-xs font-semibold transition ${
              activeSubTab === 'farm'
                ? 'bg-white text-emerald-950 shadow-xs'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Farm-Owned Batches ({db.farmIncubations.length})
          </button>
        </div>

        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input
            type="text"
            placeholder="Search by customer, batch ID..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-slate-50 focus:bg-white focus:outline-hidden"
          />
        </div>
      </div>

      {/* Subtab 1: Commercial Customer Incubation Register */}
      {activeSubTab === 'customer' && (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filteredCustomerBatches.map((batch) => {
              const isHatched = batch.status === 'hatched' || batch.status === 'collected';
              const isCollected = batch.isCollected;

              return (
                <div
                  key={batch.id}
                  className={`bg-white rounded-xl border p-4 shadow-xs flex flex-col justify-between transition hover:shadow-md ${
                    isCollected
                      ? 'border-slate-200 opacity-90'
                      : batch.status === 'lockdown'
                      ? 'border-amber-400 ring-2 ring-amber-100'
                      : 'border-slate-200'
                  }`}
                >
                  <div>
                    {/* Header */}
                    <div className="flex items-center justify-between text-xs mb-1">
                      <span className="font-mono text-[11px] font-bold text-slate-500">{batch.batchCode}</span>
                      <span
                        className={`text-[10px] font-bold px-2 py-0.5 rounded capitalize ${
                          batch.status === 'collected'
                            ? 'bg-slate-100 text-slate-700'
                            : batch.status === 'hatched'
                            ? 'bg-emerald-100 text-emerald-800'
                            : batch.status === 'lockdown'
                            ? 'bg-amber-100 text-amber-800'
                            : 'bg-cyan-100 text-cyan-800'
                        }`}
                      >
                        {batch.status.replace('_', ' ')}
                      </span>
                    </div>

                    <h3 className="text-base font-bold text-slate-900 leading-snug">{batch.customerName}</h3>
                    <p className="text-xs text-slate-600 mt-0.5">{batch.customerPhone} · {batch.customerVillage || 'Liwonde'}</p>

                    {/* Batch details */}
                    <div className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100 text-xs">
                      <div>
                        <span className="text-slate-400 text-[10px] uppercase font-semibold">Species</span>
                        <p className="font-bold text-slate-800 mt-0.5">{batch.species}</p>
                      </div>
                      <div>
                        <span className="text-slate-400 text-[10px] uppercase font-semibold">Eggs Accepted</span>
                        <p className="text-lg font-black text-slate-900 mt-0.5">
                          {batch.eggsAccepted}{' '}
                          <span className="text-[11px] font-normal text-slate-400">
                            ({batch.eggsReceived} recv)
                          </span>
                        </p>
                      </div>
                      <div>
                        <span className="text-slate-400 text-[10px] uppercase font-semibold">Setting Date</span>
                        <p className="font-medium text-slate-700 mt-0.5">{formatDate(batch.dateSet)}</p>
                      </div>
                      <div>
                        <span className="text-slate-400 text-[10px] uppercase font-semibold">Expected Hatch</span>
                        <p className="font-bold text-emerald-800 mt-0.5">{formatDate(batch.expectedHatchDate)}</p>
                      </div>
                    </div>

                    {/* Financial summary */}
                    <div className="mt-3 bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-xs space-y-1">
                      <div className="flex justify-between">
                        <span className="text-slate-500">Service Charge:</span>
                        <span className="font-bold text-slate-900">{formatMWK(batch.totalCharge)}</span>
                      </div>
                      <div className="flex justify-between text-emerald-700">
                        <span>Paid to date:</span>
                        <span className="font-semibold">{formatMWK(batch.amountPaid)}</span>
                      </div>
                      <div className="flex justify-between font-bold pt-1 border-t border-slate-200">
                        <span>Balance Due:</span>
                        <span className={batch.balanceDue > 0 ? 'text-amber-800 font-extrabold' : 'text-emerald-700'}>
                          {formatMWK(batch.balanceDue)}
                        </span>
                      </div>
                    </div>

                    {/* Chick hatch results if recorded */}
                    {batch.chicksHatched !== undefined && (
                      <div className="mt-2 text-xs bg-emerald-50 text-emerald-950 p-2 rounded border border-emerald-200 font-medium">
                        Hatched: <strong>{batch.chicksHatched} chicks</strong> · Delivered:{' '}
                        <strong>{batch.chicksDelivered || batch.chicksHatched}</strong>
                      </div>
                    )}
                  </div>

                  {/* Actions Bar */}
                  <div className="mt-4 pt-3 border-t border-slate-100 space-y-2">
                    {/* Customer WhatsApp Notification Triggers */}
                    <div className="grid grid-cols-2 gap-1.5">
                      <button
                        onClick={() => setSelectedWhatsAppBatch({ batch, alertType: 'hatching_collection' })}
                        className="py-1.5 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 rounded-lg text-xs font-bold flex items-center justify-center gap-1 border border-emerald-200 transition"
                        title="Send WhatsApp alert that chicks are ready for pickup"
                      >
                        <MessageCircle className="w-3.5 h-3.5 text-emerald-700" />
                        <span>Hatch Alert</span>
                      </button>
                      <button
                        onClick={() => setSelectedWhatsAppBatch({ batch, alertType: 'setting_confirmation' })}
                        className="py-1.5 px-2 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 border border-slate-200 transition"
                        title="Send WhatsApp confirmation when eggs are set"
                      >
                        <Share2 className="w-3.5 h-3.5 text-slate-500" />
                        <span>Setting Alert</span>
                      </button>
                    </div>

                    <div className="flex flex-wrap gap-1.5">
                      {/* View & Share Receipt */}
                      <button
                        onClick={() => setSelectedReceiptBatch(batch)}
                        className="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition"
                        title="View & Share Receipt"
                      >
                        <Printer className="w-3.5 h-3.5 text-slate-500" />
                        <span>Receipt</span>
                      </button>

                      {/* Add Payment if balance due */}
                      {batch.balanceDue > 0 && (
                        <button
                          onClick={() => {
                            setSelectedCustBatchForPayment(batch);
                            setPayAmount(batch.balanceDue);
                          }}
                          className="flex-1 py-1.5 px-2 bg-amber-50 hover:bg-amber-100 text-amber-900 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition"
                        >
                          <DollarSign className="w-3.5 h-3.5 text-amber-700" />
                          <span>Add Pay</span>
                        </button>
                      )}

                      {/* Record Hatch & Deliver */}
                      {!isCollected && (
                        <button
                          onClick={() => {
                            setSelectedCustBatchForHatch(batch);
                            setCustHatchedChicks(Math.round(batch.eggsAccepted * 0.8));
                            setCustDeliveredChicks(Math.round(batch.eggsAccepted * 0.8));
                          }}
                          className="py-1.5 px-3 bg-emerald-800 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1 transition"
                        >
                          <CheckCircle className="w-3.5 h-3.5" />
                          <span>Hatch</span>
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Subtab 2: Farm-Owned Incubation Batches */}
      {activeSubTab === 'farm' && (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filteredFarmBatches.map((batch) => {
              const isDone = batch.status === 'completed';

              return (
                <div
                  key={batch.id}
                  className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between hover:shadow-md transition"
                >
                  <div>
                    <div className="flex items-center justify-between text-xs mb-1">
                      <span className="font-mono text-[11px] font-bold text-slate-500">{batch.batchCode}</span>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 capitalize">
                        {batch.status.replace('_', ' ')}
                      </span>
                    </div>

                    <h3 className="text-base font-bold text-slate-900">{batch.species}</h3>
                    <p className="text-xs text-slate-500">Source: {batch.breedSource}</p>

                    <div className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100 text-xs">
                      <div>
                        <span className="text-slate-400 text-[10px] uppercase font-semibold">Eggs Set</span>
                        <p className="text-lg font-black text-slate-900 mt-0.5">{batch.eggsSet}</p>
                      </div>
                      <div>
                        <span className="text-slate-400 text-[10px] uppercase font-semibold">Incubator</span>
                        <p className="text-xs font-medium text-slate-800 mt-0.5 truncate">{batch.incubatorId}</p>
                      </div>
                      <div>
                        <span className="text-slate-400 text-[10px] uppercase font-semibold">Expected Hatch</span>
                        <p className="font-bold text-emerald-800 mt-0.5">{formatDate(batch.expectedHatchDate)}</p>
                      </div>
                      <div>
                        <span className="text-slate-400 text-[10px] uppercase font-semibold">Lockdown Date</span>
                        <p className="font-medium text-slate-700 mt-0.5">{formatDate(batch.lockdownDate)}</p>
                      </div>
                    </div>

                    {/* Candling Results */}
                    {batch.firstCandlingResult ? (
                      <div className="mt-3 bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-xs">
                        <span className="font-semibold text-slate-700">Day 7 Candling:</span>{' '}
                        <span className="text-emerald-800 font-bold">
                          {batch.firstCandlingResult.fertileCount} fertile
                        </span>{' '}
                        ({(
                          (batch.firstCandlingResult.fertileCount / batch.eggsSet) *
                          100
                        ).toFixed(0)}
                        % fertility)
                      </div>
                    ) : (
                      <div className="mt-3 text-[11px] text-slate-400 italic">
                        First candling due on {formatDate(batch.firstCandlingDate)}
                      </div>
                    )}

                    {/* Hatch result */}
                    {batch.chicksHatched !== undefined && (
                      <div className="mt-2 bg-emerald-50 text-emerald-950 p-2.5 rounded-lg border border-emerald-200 text-xs">
                        <p className="font-bold text-sm">
                          {batch.chicksHatched} chicks hatched ({batch.hatchPercentage}% hatch rate)
                        </p>
                        {batch.chickQualityNotes && (
                          <p className="text-[11px] text-emerald-800 mt-0.5">{batch.chickQualityNotes}</p>
                        )}
                      </div>
                    )}
                  </div>

                  {/* Actions */}
                  <div className="mt-4 pt-3 border-t border-slate-100 flex gap-2">
                    {!batch.firstCandlingResult && !isDone && (
                      <button
                        onClick={() => {
                          setSelectedBatchForCandling(batch);
                          setCandlingFertile(Math.round(batch.eggsSet * 0.9));
                          setCandlingInfertile(Math.round(batch.eggsSet * 0.1));
                        }}
                        className="flex-1 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs rounded-lg flex items-center justify-center gap-1"
                      >
                        <Eye className="w-3.5 h-3.5" />
                        <span>Log Candling</span>
                      </button>
                    )}

                    {!isDone && (
                      <button
                        onClick={() => {
                          setSelectedBatchForHatch(batch);
                          setHatchChicks(
                            batch.firstCandlingResult
                              ? Math.round(batch.firstCandlingResult.fertileCount * 0.9)
                              : Math.round(batch.eggsSet * 0.8)
                          );
                        }}
                        className="flex-1 py-1.5 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg flex items-center justify-center gap-1"
                      >
                        <CheckCircle className="w-3.5 h-3.5" />
                        <span>Log Hatch</span>
                      </button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* MODAL 1: Register Commercial Customer Incubation Batch */}
      {showAddCustomerModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
          <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto max-h-[92vh] overflow-y-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">
              Register Commercial Customer Incubation
            </h3>
            <p className="text-xs text-slate-500 mb-4">
              Enter customer information, egg intake verification, incubator assignment, and deposit.
            </p>

            <form onSubmit={handleCreateCustomerBatch} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Customer Full Name</label>
                  <input
                    type="text"
                    placeholder="e.g. Patrick Phiri"
                    value={custName}
                    onChange={(e) => setCustName(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Phone Number</label>
                  <input
                    type="tel"
                    placeholder="+265 99... / 88..."
                    value={custPhone}
                    onChange={(e) => setCustPhone(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Village / Address</label>
                  <input
                    type="text"
                    placeholder="e.g. Ulongwe, Machinga Road"
                    value={custVillage}
                    onChange={(e) => setCustVillage(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Species</label>
                  <select
                    value={custSpecies}
                    onChange={(e) => {
                      const sp = e.target.value as PoultrySpecies;
                      setCustSpecies(sp);
                      if (sp === 'Turkey' || sp === 'Duck' || sp === 'Guinea fowl') setCustUnitRate(500);
                      else if (sp === 'Quail') setCustUnitRate(150);
                      else setCustUnitRate(250);
                      setCustExpectedHatchDate(computeHatchDate(custSettingDate, sp));
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="Local chicken">Local chicken (21 days)</option>
                    <option value="Mikolongwe chicken">Mikolongwe chicken (21 days)</option>
                    <option value="Sasso chicken">Sasso chicken (21 days)</option>
                    <option value="Guinea fowl">Guinea fowl (Nkhanga - 28 days)</option>
                    <option value="Turkey">Turkey (28 days)</option>
                    <option value="Duck">Duck (28 days)</option>
                    <option value="Quail">Quail (17 days)</option>
                  </select>
                </div>
              </div>

              {/* Setting and Hatching Dates */}
              <div className="grid grid-cols-2 gap-3 bg-amber-50/60 p-3 rounded-xl border border-amber-200">
                <div>
                  <label className="block font-bold text-amber-950 mb-1">
                    Date Put in Hatching Trays (Setting)
                  </label>
                  <input
                    type="date"
                    value={custSettingDate}
                    onChange={(e) => {
                      setCustSettingDate(e.target.value);
                      setCustExpectedHatchDate(computeHatchDate(e.target.value, custSpecies));
                    }}
                    className="w-full p-2 border border-amber-300 rounded-lg bg-white font-medium"
                    required
                  />
                </div>
                <div>
                  <label className="block font-bold text-emerald-950 mb-1">
                    Hatching Date (Expected)
                  </label>
                  <input
                    type="date"
                    value={custExpectedHatchDate}
                    onChange={(e) => setCustExpectedHatchDate(e.target.value)}
                    className="w-full p-2 border border-emerald-300 rounded-lg bg-white font-bold text-emerald-950"
                    required
                  />
                </div>
              </div>

              {/* Egg Intake Verification */}
              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                <span className="font-semibold text-slate-700 block">Egg Intake Physical Inspection</span>
                <div className="grid grid-cols-3 gap-2">
                  <div>
                    <label className="block text-[11px] font-medium text-slate-600 mb-0.5">Received</label>
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
                    <label className="block text-[11px] font-bold text-emerald-800 mb-0.5">Accepted</label>
                    <input
                      type="number"
                      value={custEggsAccepted}
                      onChange={(e) => setCustEggsAccepted(Number(e.target.value))}
                      className="w-full p-1.5 border border-emerald-400 bg-white rounded font-bold text-emerald-950"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-medium text-rose-700 mb-0.5">Rejected</label>
                    <input
                      type="number"
                      value={custEggsRejected}
                      onChange={(e) => {
                        const rej = Number(e.target.value);
                        setCustEggsRejected(rej);
                        setCustEggsAccepted(Math.max(0, custEggsReceived - rej));
                      }}
                      className="w-full p-1.5 border border-rose-300 bg-white rounded font-bold text-rose-900"
                    />
                  </div>
                </div>

                {custEggsRejected > 0 && (
                  <div>
                    <label className="block text-[10px] text-slate-500 mb-0.5">Reason for Rejected Eggs</label>
                    <input
                      type="text"
                      placeholder="e.g. Hairline cracks, excessively dirty, abnormal shape"
                      value={custRejectionReason}
                      onChange={(e) => setCustRejectionReason(e.target.value)}
                      className="w-full p-1.5 border border-slate-300 rounded bg-white"
                    />
                  </div>
                )}
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Assign to Incubator</label>
                <input
                  type="text"
                  value={custIncubatorId}
                  onChange={(e) => setCustIncubatorId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              {/* Pricing & Deposit */}
              <div className="grid grid-cols-2 gap-3 bg-emerald-50/60 p-3 rounded-xl border border-emerald-200">
                <div>
                  <label className="block font-semibold text-emerald-950 mb-1">Fee per Egg (MK)</label>
                  <input
                    type="number"
                    value={custUnitRate}
                    onChange={(e) => setCustUnitRate(Number(e.target.value))}
                    className="w-full p-2 border border-emerald-300 rounded-lg bg-white font-bold"
                    required
                  />
                  <span className="text-[10px] text-emerald-800">
                    Total: MK {(custEggsAccepted * custUnitRate).toLocaleString()}
                  </span>
                </div>
                <div>
                  <label className="block font-semibold text-emerald-950 mb-1">Deposit Paid (MK)</label>
                  <input
                    type="number"
                    value={custDeposit}
                    onChange={(e) => setCustDeposit(Number(e.target.value))}
                    className="w-full p-2 border border-emerald-300 rounded-lg bg-white font-bold"
                  />
                  <span className="text-[10px] text-emerald-800">
                    Balance: MK {Math.max(0, custEggsAccepted * custUnitRate - custDeposit).toLocaleString()}
                  </span>
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Deposit Payment Method</label>
                <select
                  value={custDepositMethod}
                  onChange={(e) => setCustDepositMethod(e.target.value as PaymentMethod)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                >
                  <option value="airtel_money">Airtel Money</option>
                  <option value="tnm_mpamba">TNM Mpamba</option>
                  <option value="cash">Cash (Off-line)</option>
                  <option value="bank_transfer">Bank Transfer (National Bank / Standard)</option>
                </select>
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Notes / Instructions</label>
                <input
                  type="text"
                  placeholder="e.g. Customer will pick up in person with bicycle / car"
                  value={custNotes}
                  onChange={(e) => setCustNotes(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowAddCustomerModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Register & Set in Incubator
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 2: Add Farm Batch Modal */}
      {showAddFarmModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
          <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Set Farm Incubation Batch</h3>
            <p className="text-xs text-slate-500 mb-4">
              Allocate farm-collected eggs to digital incubators for chick production.
            </p>

            <form onSubmit={handleCreateFarmBatch} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Species</label>
                  <select
                    value={farmSpecies}
                    onChange={(e) => {
                      const sp = e.target.value as PoultrySpecies;
                      setFarmSpecies(sp);
                      setFarmExpectedHatchDate(computeHatchDate(farmSettingDate, sp));
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="Mikolongwe chicken">Mikolongwe chicken (21 days)</option>
                    <option value="Sasso chicken">Sasso chicken (21 days)</option>
                    <option value="Local chicken">Local chicken (21 days)</option>
                    <option value="Guinea fowl">Guinea fowl (Nkhanga - 28 days)</option>
                    <option value="Turkey">Turkey (28 days)</option>
                    <option value="Duck">Duck (28 days)</option>
                    <option value="Quail">Quail (17 days)</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Source Flock</label>
                  <select
                    value={farmFlockId}
                    onChange={(e) => setFarmFlockId(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    {db.flocks.map((f) => (
                      <option key={f.id} value={f.id}>
                        {f.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Explicit Setting Date and Hatching Date */}
              <div className="grid grid-cols-2 gap-3 bg-emerald-50/60 p-3 rounded-xl border border-emerald-200">
                <div>
                  <label className="block font-bold text-slate-800 mb-1">
                    Date Put in Hatching Trays (Setting Date)
                  </label>
                  <input
                    type="date"
                    value={farmSettingDate}
                    onChange={(e) => {
                      setFarmSettingDate(e.target.value);
                      setFarmExpectedHatchDate(computeHatchDate(e.target.value, farmSpecies));
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white font-medium"
                    required
                  />
                </div>
                <div>
                  <label className="block font-bold text-emerald-950 mb-1">
                    Hatching Date (Expected)
                  </label>
                  <input
                    type="date"
                    value={farmExpectedHatchDate}
                    onChange={(e) => setFarmExpectedHatchDate(e.target.value)}
                    className="w-full p-2 border border-emerald-300 rounded-lg bg-white font-bold text-emerald-950"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Number of Eggs Set</label>
                  <input
                    type="number"
                    min="1"
                    value={farmEggsSet}
                    onChange={(e) => setFarmEggsSet(Number(e.target.value))}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Incubator Unit</label>
                  <input
                    type="text"
                    value={farmIncubatorId}
                    onChange={(e) => setFarmIncubatorId(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Notes</label>
                <input
                  type="text"
                  placeholder="e.g. Solar inverter test completed, temperature calibrated"
                  value={farmNotes}
                  onChange={(e) => setFarmNotes(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowAddFarmModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Start Farm Batch
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 3: Log Candling for Farm Batch */}
      {selectedBatchForCandling && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">
              Candling Results: {selectedBatchForCandling.batchCode}
            </h3>
            <p className="text-xs text-slate-500 mb-4">
              Total eggs set: <strong className="text-slate-900">{selectedBatchForCandling.eggsSet}</strong>
            </p>

            <form onSubmit={handleSaveCandling} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-emerald-800 mb-1">Fertile Eggs Identified</label>
                <input
                  type="number"
                  value={candlingFertile}
                  onChange={(e) => {
                    const f = Number(e.target.value);
                    setCandlingFertile(f);
                    setCandlingInfertile(Math.max(0, selectedBatchForCandling.eggsSet - f));
                  }}
                  className="w-full p-2 border border-emerald-300 rounded-lg font-bold text-emerald-950"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-rose-700 mb-1">Infertile / Clear Eggs Removed</label>
                <input
                  type="number"
                  value={candlingInfertile}
                  onChange={(e) => setCandlingInfertile(Number(e.target.value))}
                  className="w-full p-2 border border-rose-300 rounded-lg"
                  required
                />
              </div>

              <div className="text-[11px] text-slate-500">
                Estimated Fertility Rate:{' '}
                <strong>
                  {selectedBatchForCandling.eggsSet > 0
                    ? ((candlingFertile / selectedBatchForCandling.eggsSet) * 100).toFixed(1)
                    : 0}
                  %
                </strong>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setSelectedBatchForCandling(null)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Save Candling
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 4: Log Farm Hatch */}
      {selectedBatchForHatch && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">
              Record Final Hatch: {selectedBatchForHatch.batchCode}
            </h3>
            <p className="text-xs text-slate-500 mb-4">
              Species: {selectedBatchForHatch.species} • Total Set: {selectedBatchForHatch.eggsSet}
            </p>

            <form onSubmit={handleSaveFarmHatch} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">
                  Hatching Date (Actual Hatch Date)
                </label>
                <input
                  type="date"
                  value={farmHatchDate}
                  onChange={(e) => setFarmHatchDate(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white font-medium"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-emerald-800 mb-1">Chicks Hatched Alive</label>
                <input
                  type="number"
                  value={hatchChicks}
                  onChange={(e) => {
                    const h = Number(e.target.value);
                    setHatchChicks(h);
                    setHatchUnhatched(Math.max(0, selectedBatchForHatch.eggsSet - h));
                  }}
                  className="w-full p-2 border border-emerald-300 rounded-lg font-bold text-emerald-950 text-base"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-rose-700 mb-1">Unhatched / Dead in Shell</label>
                <input
                  type="number"
                  value={hatchUnhatched}
                  onChange={(e) => setHatchUnhatched(Number(e.target.value))}
                  className="w-full p-2 border border-rose-300 rounded-lg"
                  required
                />
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Chick Quality & Health Notes</label>
                <input
                  type="text"
                  value={hatchQualityNotes}
                  onChange={(e) => setHatchQualityNotes(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="text-[11px] text-slate-500">
                Calculated Hatch Rate:{' '}
                <strong>
                  {selectedBatchForHatch.eggsSet > 0
                    ? ((hatchChicks / selectedBatchForHatch.eggsSet) * 100).toFixed(1)
                    : 0}
                  %
                </strong>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setSelectedBatchForHatch(null)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Complete Hatch
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 5: Add Customer Payment */}
      {selectedCustBatchForPayment && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">
              Receive Payment: {selectedCustBatchForPayment.customerName}
            </h3>
            <p className="text-xs text-slate-500 mb-3">
              Batch: {selectedCustBatchForPayment.batchCode} • Remaining Balance:{' '}
              <strong className="text-amber-800">{formatMWK(selectedCustBatchForPayment.balanceDue)}</strong>
            </p>

            <form onSubmit={handleSaveCustomerPayment} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Amount to Pay in MK</label>
                <input
                  type="number"
                  min="1"
                  max={selectedCustBatchForPayment.balanceDue}
                  value={payAmount}
                  onChange={(e) => setPayAmount(Number(e.target.value))}
                  className="w-full p-2 border border-slate-300 rounded-lg font-bold text-base text-slate-900"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Payment Method</label>
                <select
                  value={payMethod}
                  onChange={(e) => setPayMethod(e.target.value as PaymentMethod)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                >
                  <option value="cash">Cash (Farm Gate)</option>
                  <option value="airtel_money">Airtel Money</option>
                  <option value="tnm_mpamba">TNM Mpamba</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Receipt / Ref Number</label>
                <input
                  type="text"
                  value={payReceiptNo}
                  onChange={(e) => setPayReceiptNo(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Notes</label>
                <input
                  type="text"
                  placeholder="e.g. Paid in full upon collection"
                  value={payNotes}
                  onChange={(e) => setPayNotes(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setSelectedCustBatchForPayment(null)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Save Payment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 6: Customer Hatch Delivery & Collection */}
      {selectedCustBatchForHatch && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">
              Record Customer Hatch: {selectedCustBatchForHatch.customerName}
            </h3>
            <p className="text-xs text-slate-500 mb-3">
              Batch: {selectedCustBatchForHatch.batchCode} ({selectedCustBatchForHatch.eggsAccepted} eggs accepted)
            </p>

            <form onSubmit={handleSaveCustomerHatch} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">
                  Hatching Date (Actual Hatch Date)
                </label>
                <input
                  type="date"
                  value={custHatchDate}
                  onChange={(e) => setCustHatchDate(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white font-medium"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-emerald-800 mb-1">Chicks Hatched Alive</label>
                <input
                  type="number"
                  value={custHatchedChicks}
                  onChange={(e) => {
                    const h = Number(e.target.value);
                    setCustHatchedChicks(h);
                    setCustDeliveredChicks(h);
                    setCustUnhatchedCount(Math.max(0, selectedCustBatchForHatch.eggsAccepted - h));
                  }}
                  className="w-full p-2 border border-emerald-300 rounded-lg font-bold text-emerald-950 text-base"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Chicks Delivered to Customer</label>
                <input
                  type="number"
                  value={custDeliveredChicks}
                  onChange={(e) => setCustDeliveredChicks(Number(e.target.value))}
                  className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-rose-700 mb-1">Unhatched Eggs</label>
                <input
                  type="number"
                  value={custUnhatchedCount}
                  onChange={(e) => setCustUnhatchedCount(Number(e.target.value))}
                  className="w-full p-2 border border-rose-300 rounded-lg"
                  required
                />
              </div>

              <div className="flex items-center gap-2 pt-2">
                <input
                  type="checkbox"
                  id="collected-check"
                  checked={custCollectedCheck}
                  onChange={(e) => setCustCollectedCheck(e.target.checked)}
                  className="w-4 h-4 rounded text-emerald-800 border-slate-300"
                />
                <label htmlFor="collected-check" className="font-semibold text-slate-800 cursor-pointer">
                  Customer has physically collected chicks
                </label>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setSelectedCustBatchForHatch(null)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Save & Update Batch
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Official Receipt Modal when selected */}
      {selectedReceiptBatch && (
        <ReceiptModal
          incubationBatch={selectedReceiptBatch}
          onClose={() => setSelectedReceiptBatch(null)}
        />
      )}

      {/* WhatsApp Hatch / Setting Alert Modal */}
      {selectedWhatsAppBatch && (
        <WhatsAppAlertModal
          batch={selectedWhatsAppBatch.batch}
          alertType={selectedWhatsAppBatch.alertType}
          settings={settings}
          onClose={() => setSelectedWhatsAppBatch(null)}
        />
      )}
    </div>
  );
};
