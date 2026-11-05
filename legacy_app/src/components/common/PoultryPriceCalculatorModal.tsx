import React, { useState } from 'react';
import {
  calculateBirdPrice,
  getSpeciesRateCard,
  POULTRY_PRICING_CONFIG,
  PricePoint,
} from '../../utils/pricing';
import { formatMWK } from '../../utils/i18n';
import {
  Calculator,
  Bird,
  Table,
  Check,
  Copy,
  X,
  Sparkles,
  Info,
  Calendar,
  Layers,
  ArrowRight,
} from 'lucide-react';

interface PoultryPriceCalculatorModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialSpecies?: string;
  initialWeeks?: number;
  onSelectPrice?: (unitPrice: number, species: string, weeks: number, qty: number) => void;
}

export const PoultryPriceCalculatorModal: React.FC<PoultryPriceCalculatorModalProps> = ({
  isOpen,
  onClose,
  initialSpecies = 'mikolongwe',
  initialWeeks = 1,
  onSelectPrice,
}) => {
  const [selectedSpecies, setSelectedSpecies] = useState<string>(initialSpecies);
  const [isDayOld, setIsDayOld] = useState(false);
  const [ageWeeks, setAgeWeeks] = useState(initialWeeks);
  const [quantity, setQuantity] = useState(1);
  const [activeView, setActiveView] = useState<'calculator' | 'rate_table'>('calculator');
  const [copiedPrice, setCopiedPrice] = useState(false);

  if (!isOpen) return null;

  const currentConfig = POULTRY_PRICING_CONFIG[selectedSpecies] || POULTRY_PRICING_CONFIG['mikolongwe'];
  const unitPrice = calculateBirdPrice(selectedSpecies, isDayOld ? 0 : ageWeeks, isDayOld);
  const totalPrice = unitPrice * (Number(quantity) || 1);
  const rateCard: PricePoint[] = getSpeciesRateCard(selectedSpecies);

  const handleCopy = () => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(
        `${currentConfig.displayName} (${isDayOld ? 'Day-Old' : `Week ${ageWeeks}`}): ${formatMWK(unitPrice)} each`
      );
      setCopiedPrice(true);
      setTimeout(() => setCopiedPrice(false), 2000);
    }
  };

  const maxWeeksForSpecies = selectedSpecies === 'quail' ? 10 : 20;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
      <div className="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in zoom-in-95 duration-200">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-emerald-950 via-emerald-900 to-slate-900 text-white p-4 sm:p-5 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-400/40 flex items-center justify-center shrink-0">
              <Calculator className="w-5 h-5 text-amber-300" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-wider">
                  Official Rate Card
                </span>
                <span className="text-xs text-emerald-200 font-medium">Msantha Poultry Farm</span>
              </div>
              <h2 className="text-base font-bold text-white mt-0.5">
                Poultry Age-Based Pricing & Rate Matrix
              </h2>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 rounded-lg text-emerald-200 hover:text-white hover:bg-emerald-800/80 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* View Toggle */}
        <div className="flex border-b border-slate-200 bg-slate-50 px-4 pt-2 gap-2 text-xs font-semibold">
          <button
            onClick={() => setActiveView('calculator')}
            className={`px-3 py-2 border-b-2 transition flex items-center gap-1.5 ${
              activeView === 'calculator'
                ? 'border-emerald-700 text-emerald-900 bg-white rounded-t-lg'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Calculator className="w-3.5 h-3.5" />
            <span>Interactive Calculator</span>
          </button>
          <button
            onClick={() => setActiveView('rate_table')}
            className={`px-3 py-2 border-b-2 transition flex items-center gap-1.5 ${
              activeView === 'rate_table'
                ? 'border-emerald-700 text-emerald-900 bg-white rounded-t-lg'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Table className="w-3.5 h-3.5" />
            <span>Full Weekly Rate Card Table</span>
          </button>
        </div>

        <div className="p-4 sm:p-5 max-h-[70vh] overflow-y-auto space-y-4">
          
          {/* Species Selector Tabs */}
          <div className="space-y-1.5">
            <label className="text-xs font-bold text-slate-700">Select Poultry Species:</label>
            <div className="grid grid-cols-2 sm:grid-cols-5 gap-1.5">
              {Object.keys(POULTRY_PRICING_CONFIG).map((key) => {
                const conf = POULTRY_PRICING_CONFIG[key];
                const isSelected = selectedSpecies === key;
                return (
                  <button
                    key={key}
                    type="button"
                    onClick={() => {
                      setSelectedSpecies(key);
                      if (key === 'quail' && ageWeeks > 10) setAgeWeeks(10);
                    }}
                    className={`py-2 px-2 rounded-xl text-xs font-bold text-center border transition ${
                      isSelected
                        ? 'bg-emerald-800 text-white border-emerald-800 shadow-xs'
                        : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
                    }`}
                  >
                    <p className="truncate">{conf.displayName}</p>
                    <p className={`text-[10px] font-normal ${isSelected ? 'text-amber-300' : 'text-slate-400'}`}>
                      Day-old: {formatMWK(conf.dayOldPriceMwk)}
                    </p>
                  </button>
                );
              })}
            </div>
          </div>

          {activeView === 'calculator' ? (
            <div className="space-y-4">
              
              {/* Formula Card */}
              <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-950 flex items-start gap-2.5">
                <Info className="w-4 h-4 text-emerald-700 shrink-0 mt-0.5" />
                <div>
                  <span className="font-bold">{currentConfig.displayName} Official Price Formula:</span>
                  <p className="text-[11px] text-emerald-800 mt-0.5 font-medium">{currentConfig.description}</p>
                </div>
              </div>

              {/* Age / Stage Selector */}
              <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                <div className="flex items-center justify-between">
                  <label className="text-xs font-bold text-slate-800">Bird Stage & Age:</label>
                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => setIsDayOld(true)}
                      className={`px-3 py-1 rounded-lg text-xs font-bold transition ${
                        isDayOld
                          ? 'bg-amber-500 text-slate-950 shadow-xs'
                          : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'
                      }`}
                    >
                      Day-Old Chick (DOC)
                    </button>
                    <button
                      type="button"
                      onClick={() => setIsDayOld(false)}
                      className={`px-3 py-1 rounded-lg text-xs font-bold transition ${
                        !isDayOld
                          ? 'bg-emerald-800 text-white shadow-xs'
                          : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'
                      }`}
                    >
                      Grower / Adult (Weeks)
                    </button>
                  </div>
                </div>

                {!isDayOld && (
                  <div className="space-y-2 pt-1">
                    <div className="flex items-center justify-between text-xs font-bold text-slate-700">
                      <span>Age: {ageWeeks} Week{ageWeeks === 1 ? '' : 's'} ({ageWeeks * 7} Days)</span>
                      <span className="text-[11px] text-emerald-700">Max formula: {maxWeeksForSpecies} Weeks</span>
                    </div>
                    <input
                      type="range"
                      min={1}
                      max={maxWeeksForSpecies}
                      value={ageWeeks}
                      onChange={(e) => setAgeWeeks(Number(e.target.value))}
                      className="w-full accent-emerald-700 cursor-pointer h-2 bg-slate-200 rounded-lg"
                    />
                    <div className="flex justify-between text-[10px] text-slate-400 font-mono">
                      <span>1 Wk</span>
                      <span>5 Wks</span>
                      <span>10 Wks</span>
                      <span>15 Wks</span>
                      <span>{maxWeeksForSpecies} Wks</span>
                    </div>
                  </div>
                )}

                {/* Quantity */}
                <div className="pt-2 border-t border-slate-200 flex items-center justify-between">
                  <label className="text-xs font-bold text-slate-700">Quantity (Birds):</label>
                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => setQuantity(Math.max(1, quantity - 5))}
                      className="w-7 h-7 rounded-lg bg-slate-200 hover:bg-slate-300 font-bold text-xs"
                    >
                      -5
                    </button>
                    <input
                      type="number"
                      min={1}
                      value={quantity}
                      onChange={(e) => setQuantity(Math.max(1, Number(e.target.value) || 1))}
                      className="w-20 text-center font-bold text-sm py-1 border border-slate-300 rounded-lg bg-white"
                    />
                    <button
                      type="button"
                      onClick={() => setQuantity(quantity + 5)}
                      className="w-7 h-7 rounded-lg bg-slate-200 hover:bg-slate-300 font-bold text-xs"
                    >
                      +5
                    </button>
                  </div>
                </div>
              </div>

              {/* Price Calculation Result Banner */}
              <div className="bg-gradient-to-r from-emerald-950 via-emerald-900 to-slate-900 text-white rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                  <span className="text-[10px] uppercase font-bold text-amber-300 tracking-wider">
                    Official Selling Rate
                  </span>
                  <div className="flex items-baseline gap-2 mt-0.5">
                    <span className="text-2xl sm:text-3xl font-black text-white">
                      {formatMWK(unitPrice)}
                    </span>
                    <span className="text-xs text-emerald-300">/ bird</span>
                  </div>
                  <p className="text-xs text-emerald-200/80 mt-1">
                    Total for {quantity} bird{quantity > 1 ? 's' : ''}:{' '}
                    <strong className="text-amber-300 font-black text-sm">{formatMWK(totalPrice)}</strong>
                  </p>
                </div>

                <div className="flex items-center gap-2 w-full sm:w-auto shrink-0 justify-end">
                  <button
                    type="button"
                    onClick={handleCopy}
                    className="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold flex items-center gap-1.5 transition"
                  >
                    {copiedPrice ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                    <span>{copiedPrice ? 'Copied!' : 'Copy'}</span>
                  </button>

                  {onSelectPrice && (
                    <button
                      type="button"
                      onClick={() => {
                        onSelectPrice(unitPrice, currentConfig.displayName, isDayOld ? 0 : ageWeeks, quantity);
                        onClose();
                      }}
                      className="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold shadow-md transition flex items-center gap-1"
                    >
                      <span>Apply to Record</span>
                      <ArrowRight className="w-3.5 h-3.5" />
                    </button>
                  )}
                </div>
              </div>

            </div>
          ) : (
            /* Full Weekly Rate Table View */
            <div className="space-y-3">
              <div className="border border-slate-200 rounded-xl overflow-hidden">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-100 border-b border-slate-200 text-slate-700 font-bold">
                    <tr>
                      <th className="p-2.5">Week / Stage</th>
                      <th className="p-2.5">Official Price (MWK)</th>
                      <th className="p-2.5">Formula Note</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {rateCard.map((row) => (
                      <tr key={row.week} className="hover:bg-slate-50">
                        <td className="p-2.5 font-semibold text-slate-800">{row.ageLabel}</td>
                        <td className="p-2.5 font-bold font-mono text-emerald-800">
                          {formatMWK(row.priceMwk)}
                        </td>
                        <td className="p-2.5 text-[11px] text-slate-500">{row.formulaNote}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

        </div>

        {/* Footer */}
        <div className="bg-slate-50 px-4 py-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
          <span>Msantha Farm Pricing Schedule • Liwonde</span>
          <button
            onClick={onClose}
            className="px-4 py-1.5 rounded-lg bg-slate-900 text-white font-semibold text-xs hover:bg-slate-800"
          >
            Close Calculator
          </button>
        </div>

      </div>
    </div>
  );
};
