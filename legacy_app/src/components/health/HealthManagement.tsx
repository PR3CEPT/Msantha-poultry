import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { HealthRecord, MortalityRecord, VaccinationEvent } from '../../types';
import { formatDate } from '../../utils/i18n';
import {
  Activity,
  Plus,
  AlertTriangle,
  CheckCircle2,
  Calendar,
  Skull,
  ShieldAlert,
  Info,
} from 'lucide-react';

export const HealthManagement: React.FC = () => {
  const {
    db,
    currentUser,
    addHealthRecord,
    addMortalityRecord,
    addVaccinationEvent,
    updateVaccinationStatus,
  } = useFarmData();

  const [activeTab, setActiveTab] = useState<'vaccines' | 'treatments' | 'mortality'>('vaccines');

  // Modals
  const [showVacModal, setShowVacModal] = useState(false);
  const [showHealthModal, setShowHealthModal] = useState(false);
  const [showMortModal, setShowMortModal] = useState(false);

  // Forms
  const today = new Date().toISOString().split('T')[0];

  // Vaccination form
  const [vacFlockId, setVacFlockId] = useState(db.flocks[0]?.id || '');
  const [vacName, setVacName] = useState('Newcastle Disease (Lasota)');
  const [vacDisease, setVacDisease] = useState('Newcastle Disease');
  const [vacDate, setVacDate] = useState(today);
  const [vacRoute, setVacRoute] = useState('Drinking water with skimmed milk');
  const [vacNotes, setVacNotes] = useState('');

  // Treatment form
  const [treatFlockId, setTreatFlockId] = useState(db.flocks[0]?.id || '');
  const [treatIssue, setTreatIssue] = useState('');
  const [treatSymptoms, setTreatSymptoms] = useState('');
  const [treatMedicine, setTreatMedicine] = useState('');
  const [treatDosage, setTreatDosage] = useState('');
  const [treatBatch, setTreatBatch] = useState('');
  const [treatVetAdvice, setTreatVetAdvice] = useState('');

  // Mortality form
  const [mortFlockId, setMortFlockId] = useState(db.flocks[0]?.id || '');
  const [mortCount, setMortCount] = useState(1);
  const [mortCause, setMortCause] = useState('Unknown');
  const [mortSymptoms, setMortSymptoms] = useState('');
  const [mortAction, setMortAction] = useState('Disposed and disinfected');

  // Mortality calculations
  const totalMortalityBirds = db.mortalityRecords.reduce((sum, m) => sum + m.numberOfBirds, 0);
  const totalFlockBirds = db.flocks.reduce((sum, f) => sum + f.initialQuantity, 0);
  const farmMortalityRate = totalFlockBirds > 0 ? ((totalMortalityBirds / totalFlockBirds) * 100).toFixed(1) : '0';

  const handleCreateVac = (e: React.FormEvent) => {
    e.preventDefault();
    const flock = db.flocks.find((f) => f.id === vacFlockId);
    if (!flock) return;
    addVaccinationEvent({
      flockId: flock.id,
      flockName: flock.name,
      species: flock.species,
      vaccineName: vacName,
      targetDisease: vacDisease,
      scheduledDate: vacDate,
      status: 'scheduled',
      route: vacRoute,
      notes: vacNotes,
    });
    setShowVacModal(false);
  };

  const handleCreateHealth = (e: React.FormEvent) => {
    e.preventDefault();
    const flock = db.flocks.find((f) => f.id === treatFlockId);
    if (!flock || !treatMedicine) return;
    addHealthRecord({
      date: today,
      flockId: flock.id,
      flockName: flock.name,
      healthIssue: treatIssue || 'Routine Treatment',
      symptomsObserved: treatSymptoms,
      medicineOrVaccine: treatMedicine,
      quantityAdministered: treatDosage || 'As directed by vet',
      dosageAndRoute: treatDosage,
      batchNumber: treatBatch,
      startDate: today,
      personResponsible: currentUser.name,
      vetOrSupplierAdvice: treatVetAdvice,
      status: 'ongoing',
    });
    setShowHealthModal(false);
  };

  const handleCreateMort = (e: React.FormEvent) => {
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
    setShowMortModal(false);
  };

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">Flock Health, Vaccines & Mortality</h2>
          <p className="text-xs text-slate-500">
            Vaccination calendars, biosecurity schedules, clinical treatment logs, and mortality analysis.
          </p>
        </div>

        <div className="flex items-center gap-2">
          {activeTab === 'vaccines' && (
            <button
              onClick={() => setShowVacModal(true)}
              className="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
            >
              <Plus className="w-4 h-4" />
              <span>Schedule Vaccine</span>
            </button>
          )}
          {activeTab === 'treatments' && (
            <button
              onClick={() => setShowHealthModal(true)}
              className="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
            >
              <Plus className="w-4 h-4" />
              <span>Log Treatment</span>
            </button>
          )}
          {activeTab === 'mortality' && (
            <button
              onClick={() => setShowMortModal(true)}
              className="flex items-center gap-1.5 px-3.5 py-2 bg-rose-800 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
            >
              <Skull className="w-4 h-4" />
              <span>Record Loss</span>
            </button>
          )}
        </div>
      </div>

      {/* Mandatory Veterinary Disclaimer Card */}
      <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-start gap-2.5 text-xs text-amber-900">
        <Info className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
        <div>
          <strong className="font-bold">Veterinary & Biosecurity Advisory:</strong> All health records and vaccine
          protocols serve as farm operational records only. Consult certified veterinary officers from Liwonde or
          Zomba and follow official manufacturer packaging labels before administering any medication.
        </div>
      </div>

      {/* Navigation Subtabs */}
      <div className="bg-white p-1 rounded-xl border border-slate-200 flex text-xs font-semibold">
        <button
          onClick={() => setActiveTab('vaccines')}
          className={`flex-1 py-2 rounded-lg transition ${
            activeTab === 'vaccines' ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          Vaccination Schedule ({db.vaccinationEvents.length})
        </button>
        <button
          onClick={() => setActiveTab('treatments')}
          className={`flex-1 py-2 rounded-lg transition ${
            activeTab === 'treatments' ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          Treatment & Medications ({db.healthRecords.length})
        </button>
        <button
          onClick={() => setActiveTab('mortality')}
          className={`flex-1 py-2 rounded-lg transition ${
            activeTab === 'mortality' ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
          }`}
        >
          Mortality & Losses ({totalMortalityBirds} birds · {farmMortalityRate}%)
        </button>
      </div>

      {/* Tab 1: Vaccination Schedule */}
      {activeTab === 'vaccines' && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
          <div className="p-3.5 border-b border-slate-100 flex items-center justify-between">
            <h3 className="text-sm font-bold text-slate-900">Flock Vaccination Schedule</h3>
            <span className="text-xs text-slate-500">Includes Newcastle, Gumboro, Fowl Pox & Deworming</span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                  <th className="p-3">Scheduled Date</th>
                  <th className="p-3">Flock</th>
                  <th className="p-3">Vaccine</th>
                  <th className="p-3">Disease Target</th>
                  <th className="p-3">Administration Route</th>
                  <th className="p-3">Status</th>
                  <th className="p-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {db.vaccinationEvents.map((vac) => {
                  const isDone = vac.status === 'completed';

                  return (
                    <tr key={vac.id} className="hover:bg-slate-50 transition">
                      <td className="p-3 font-semibold text-slate-900 whitespace-nowrap">
                        {formatDate(vac.scheduledDate)}
                      </td>
                      <td className="p-3 font-medium text-slate-800">{vac.flockName}</td>
                      <td className="p-3 font-bold text-emerald-900">{vac.vaccineName}</td>
                      <td className="p-3 text-slate-600">{vac.targetDisease}</td>
                      <td className="p-3 text-slate-600">{vac.route}</td>
                      <td className="p-3">
                        <span
                          className={`text-[10px] font-bold px-2 py-0.5 rounded capitalize ${
                            isDone ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                          }`}
                        >
                          {vac.status}
                        </span>
                      </td>
                      <td className="p-3 text-right">
                        {!isDone && (
                          <button
                            onClick={() => updateVaccinationStatus(vac.id, 'completed', currentUser.name)}
                            className="px-2.5 py-1 bg-emerald-700 hover:bg-emerald-600 text-white rounded-md text-[11px] font-semibold"
                          >
                            Mark Administered
                          </button>
                        )}
                        {isDone && <span className="text-slate-400 text-[11px]">Completed {formatDate(vac.completedDate || '')}</span>}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 2: Treatment & Medications Log */}
      {activeTab === 'treatments' && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
          <div className="p-3.5 border-b border-slate-100 flex items-center justify-between">
            <h3 className="text-sm font-bold text-slate-900">Treatment & Medication Register</h3>
            <span className="text-xs text-slate-500">Administered vitamins, antibiotics, and electrolytes</span>
          </div>

          <div className="divide-y divide-slate-100">
            {db.healthRecords.map((rec) => (
              <div key={rec.id} className="p-4 space-y-2 text-xs">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="font-bold text-slate-900 text-sm">{rec.medicineOrVaccine}</span>
                    <span className="text-slate-400">·</span>
                    <span className="font-semibold text-emerald-800">{rec.flockName}</span>
                  </div>
                  <span className="text-slate-500">{formatDate(rec.date)}</span>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                  <div>
                    <span className="text-slate-400 text-[10px] uppercase font-semibold">Reason / Issue</span>
                    <p className="font-medium text-slate-800 mt-0.5">{rec.healthIssue}</p>
                  </div>
                  <div>
                    <span className="text-slate-400 text-[10px] uppercase font-semibold">Dosage & Route</span>
                    <p className="font-medium text-slate-800 mt-0.5">{rec.dosageAndRoute}</p>
                  </div>
                  <div>
                    <span className="text-slate-400 text-[10px] uppercase font-semibold">Responsible Staff</span>
                    <p className="font-medium text-slate-800 mt-0.5">{rec.personResponsible}</p>
                  </div>
                </div>

                {rec.symptomsObserved && (
                  <p className="text-slate-600">
                    <strong>Symptoms:</strong> {rec.symptomsObserved}
                  </p>
                )}
                {rec.vetOrSupplierAdvice && (
                  <p className="text-slate-500 italic">
                    <strong>Vet / Supplier Advice:</strong> {rec.vetOrSupplierAdvice}
                  </p>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tab 3: Mortality Log */}
      {activeTab === 'mortality' && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
          <div className="p-3.5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Mortality & Culling Log</h3>
              <p className="text-[11px] text-slate-500">
                Formula: (Total bird loss / Initial flock stock) &times; 100 ={' '}
                <strong className="text-slate-800">{farmMortalityRate}%</strong> overall
              </p>
            </div>
            <span className="text-xs font-bold text-rose-800 bg-rose-50 px-2.5 py-1 rounded-lg">
              {totalMortalityBirds} Birds Lost
            </span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                  <th className="p-3">Date</th>
                  <th className="p-3">Flock</th>
                  <th className="p-3 text-right">Birds Lost</th>
                  <th className="p-3">Suspected Cause</th>
                  <th className="p-3">Observed Symptoms</th>
                  <th className="p-3">Action Taken</th>
                  <th className="p-3">Recorded By</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {db.mortalityRecords.map((m) => (
                  <tr key={m.id} className="hover:bg-slate-50 transition">
                    <td className="p-3 font-medium text-slate-900 whitespace-nowrap">{formatDate(m.date)}</td>
                    <td className="p-3 font-semibold text-slate-800">{m.flockName}</td>
                    <td className="p-3 text-right font-black text-rose-800">{m.numberOfBirds}</td>
                    <td className="p-3 font-medium text-slate-800">{m.suspectedCause}</td>
                    <td className="p-3 text-slate-600">{m.observedSymptoms || '-'}</td>
                    <td className="p-3 text-slate-600">{m.actionTaken}</td>
                    <td className="p-3 text-slate-500 whitespace-nowrap">{m.recordedBy}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Schedule Vaccine Modal */}
      {showVacModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Schedule Flock Vaccination</h3>
            <p className="text-xs text-slate-500 mb-4">Set target date and administration method.</p>

            <form onSubmit={handleCreateVac} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Flock</label>
                <select
                  value={vacFlockId}
                  onChange={(e) => setVacFlockId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  required
                >
                  {db.flocks.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.name} ({f.species})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Vaccine Name</label>
                <select
                  value={vacName}
                  onChange={(e) => {
                    const v = e.target.value;
                    setVacName(v);
                    if (v.includes('Newcastle')) setVacDisease('Newcastle Disease');
                    else if (v.includes('Gumboro')) setVacDisease('Infectious Bursal Disease (Gumboro)');
                    else if (v.includes('Fowl Pox')) setVacDisease('Fowl Pox');
                    else if (v.includes('Marek')) setVacDisease('Marek\'s Disease');
                    else setVacDisease('Internal Parasites');
                  }}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                >
                  <option value="Newcastle Disease (Lasota)">Newcastle Disease (Lasota / Hitchner B1)</option>
                  <option value="Gumboro (IBD Intermediate)">Gumboro (IBD Intermediate Strain)</option>
                  <option value="Fowl Pox Vaccine">Fowl Pox Wing Web Vaccine</option>
                  <option value="Marek's Disease Vaccine">Marek's Disease (HVT)</option>
                  <option value="Piperazine Dewormer">Piperazine / Levamisole Dewormer</option>
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Target Disease</label>
                  <input
                    type="text"
                    value={vacDisease}
                    onChange={(e) => setVacDisease(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Scheduled Date</label>
                  <input
                    type="date"
                    value={vacDate}
                    onChange={(e) => setVacDate(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Administration Route</label>
                <input
                  type="text"
                  value={vacRoute}
                  onChange={(e) => setVacRoute(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowVacModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Schedule Vaccine
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Log Treatment Modal */}
      {showHealthModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto max-h-[90vh] overflow-y-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Log Health Treatment</h3>
            <p className="text-xs text-slate-500 mb-4">Record medication administered to flock.</p>

            <form onSubmit={handleCreateHealth} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Flock</label>
                <select
                  value={treatFlockId}
                  onChange={(e) => setTreatFlockId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                >
                  {db.flocks.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Medicine / Product Name</label>
                <input
                  type="text"
                  placeholder="e.g. Oxytetracycline 20%, Vitalyte multivitamin"
                  value={treatMedicine}
                  onChange={(e) => setTreatMedicine(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Symptoms / Reason</label>
                <input
                  type="text"
                  placeholder="e.g. Sneezing, lethargy, decreased feed intake"
                  value={treatSymptoms}
                  onChange={(e) => setTreatSymptoms(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Dosage & Route</label>
                <input
                  type="text"
                  placeholder="e.g. 5g per 10L water for 5 consecutive days"
                  value={treatDosage}
                  onChange={(e) => setTreatDosage(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Veterinary / Supplier Advice</label>
                <input
                  type="text"
                  placeholder="Advice given by Vet or Pharmacy"
                  value={treatVetAdvice}
                  onChange={(e) => setTreatVetAdvice(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowHealthModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Save Treatment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Record Mortality Modal */}
      {showMortModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Record Bird Loss / Mortality</h3>
            <p className="text-xs text-slate-500 mb-4">
              Automatically updates flock bird count and creates auditable record.
            </p>

            <form onSubmit={handleCreateMort} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Affected Flock</label>
                <select
                  value={mortFlockId}
                  onChange={(e) => setMortFlockId(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                >
                  {db.flocks.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.name} ({f.currentQuantity} birds currently)
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-rose-800 mb-1">Number of Dead Birds</label>
                  <input
                    type="number"
                    min="1"
                    value={mortCount}
                    onChange={(e) => setMortCount(Number(e.target.value))}
                    className="w-full p-2 border border-rose-300 rounded-lg font-bold text-rose-950"
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
                    <option value="Unknown">Unknown</option>
                    <option value="Heat stress">Heat stress</option>
                    <option value="Predator / Snake">Predator / Snake</option>
                    <option value="Respiratory difficulty">Respiratory symptoms</option>
                    <option value="Smothering / Congestion">Smothering</option>
                    <option value="Culling (severe injury)">Culling (injury)</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Observed Symptoms (Optional)</label>
                <input
                  type="text"
                  placeholder="e.g. Swollen head, no discharge, congested corner"
                  value={mortSymptoms}
                  onChange={(e) => setMortSymptoms(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Action Taken</label>
                <input
                  type="text"
                  value={mortAction}
                  onChange={(e) => setMortAction(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowMortModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-rose-800 hover:bg-rose-700 text-white font-semibold rounded-xl"
                >
                  Record Mortality
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
