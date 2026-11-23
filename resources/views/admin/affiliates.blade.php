@extends('layouts.app')

@section('content')
<div class="admin-page min-h-screen bg-gray-50 py-8" x-data="affiliateManagement()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-12">
            <div>
                <a href="/admin/dashboard" class="admin-back mb-2 inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7 7-7-7"/></svg>
                    Back to Dashboard
                </a>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Affiliates</h1>
                <p class="text-slate-500 mt-2 font-medium">Manage affiliates and their withdrawal requests.</p>
            </div>
            <button @click="showAddAffiliate = true; loadEligibleUsers()" class="mt-6 md:mt-0 btn-admin-primary px-5 py-2.5 flex items-center gap-2 text-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add affiliate
            </button>
        </div>

        <!-- Withdrawals (pending first) -->
        <div class="mb-16">
            <h2 class="text-2xl font-black text-slate-900 mb-6">Withdrawal requests</h2>
            <div class="glass rounded-[40px] shadow-xl border border-slate-200/50 overflow-hidden bg-white">
                <div class="p-6 border-b border-slate-100 bg-slate-50/30 flex flex-wrap items-center gap-4">
                    <div class="flex-1 max-w-md flex gap-2">
                        <input type="text" x-model="withdrawalSearch" @keydown.enter="fetchWithdrawals(1)" placeholder="Search by affiliate name/email..." class="flex-1 px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-cyan-500 outline-none font-medium text-slate-900">
                        <button type="button" @click="fetchWithdrawals(1)" class="px-4 py-3 rounded-xl font-bold btn-admin-primary shrink-0">Search</button>
                    </div>
                    <select x-model="withdrawalStatusFilter" @change="fetchWithdrawals(1)" class="px-4 py-3 rounded-xl bg-white border border-slate-200 outline-none focus:border-cyan-500 font-bold text-slate-600">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <button @click="exportWithdrawalsCSV()" class="px-4 py-3 rounded-xl font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 shrink-0">Export CSV</button>
                    <button @click="fetchWithdrawals()" class="admin-back font-semibold flex items-center text-sm">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                </div>
                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Affiliate</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Amount</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Requested</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="w in withdrawals" :key="w.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-900" x-text="w.affiliate?.user?.full_name || '—'"></p>
                                        <p class="text-xs text-slate-500" x-text="w.affiliate?.user?.email || ''"></p>
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-slate-800" x-text="'GH₵ ' + (w.amount != null ? Number(w.amount).toFixed(2) : '0.00')"></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold uppercase"
                                              :class="{
                                                  'bg-amber-100 text-amber-800': w.status === 'pending',
                                                  'bg-green-100 text-green-800': w.status === 'approved',
                                                  'bg-red-100 text-red-800': w.status === 'rejected'
                                              }"
                                              x-text="w.status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600" x-text="w.created_at ? new Date(w.created_at).toLocaleString() : '—'"></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" @click="openViewWithdrawal(w)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200">View</button>
                                            <template x-if="w.status === 'pending'">
                                                <span class="inline-flex gap-2">
                                                    <button type="button" @click="openApprove(w)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-green-100 text-green-700 hover:bg-green-200">Approve</button>
                                                    <button type="button" @click="openReject(w)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-700 hover:bg-red-200">Reject</button>
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="withdrawals.length === 0 && !withdrawalsLoading">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">No withdrawal requests.</td>
                            </tr>
                            <tr x-show="withdrawalsLoading">
                                <td colspan="5" class="px-6 py-12 text-center"><div class="animate-spin w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full mx-auto"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Affiliates list -->
        <div>
            <h2 class="text-2xl font-black text-slate-900 mb-6">All affiliates</h2>
            <div class="glass rounded-[40px] shadow-xl border border-slate-200/50 overflow-hidden bg-white">
                <div class="p-6 border-b border-slate-100 bg-slate-50/30 flex flex-wrap items-center gap-4">
                    <div class="flex-1 max-w-md flex gap-2">
                        <input type="text" x-model="affiliateSearch" @keydown.enter="fetchAffiliates()" placeholder="Search by name, email, referral code..." class="flex-1 px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-cyan-500 outline-none font-medium text-slate-900">
                        <button type="button" @click="fetchAffiliates()" class="px-4 py-3 rounded-xl font-bold btn-admin-primary shrink-0">Search</button>
                    </div>
                    <select x-model="affiliateStatusFilter" @change="fetchAffiliates()" class="px-4 py-3 rounded-xl bg-white border border-slate-200 outline-none focus:border-cyan-500 font-bold text-slate-600">
                        <option value="">All status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <button @click="exportAffiliatesCSV()" class="px-4 py-3 rounded-xl font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 shrink-0">Export CSV</button>
                    <button @click="fetchAffiliates()" class="text-primary-600 font-bold hover:underline flex items-center">Refresh</button>
                </div>
                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">User</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Referral code</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Commission</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Balance</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Earned</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Withdrawn</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="a in affiliates" :key="a.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-900" x-text="a.user?.full_name || '—'"></p>
                                        <p class="text-xs text-slate-500" x-text="a.user?.email || ''"></p>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-sm font-bold text-slate-700" x-text="a.referral_code || '—'"></td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700" x-text="(a.commission_rate != null ? Number(a.commission_rate) : 0) + '%'"></td>
                                    <td class="px-6 py-4 text-right font-bold text-slate-800" x-text="'GH₵ ' + (a.balance != null ? Number(a.balance).toFixed(2) : '0.00')"></td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-700" x-text="'GH₵ ' + (a.total_earned != null ? Number(a.total_earned).toFixed(2) : '0.00')"></td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-600" x-text="'GH₵ ' + (a.total_withdrawn != null ? Number(a.total_withdrawn).toFixed(2) : '0.00')"></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold uppercase"
                                              :class="{
                                                  'bg-amber-100 text-amber-800': a.status === 'pending',
                                                  'bg-green-100 text-green-800': a.status === 'approved',
                                                  'bg-red-100 text-red-800': a.status === 'suspended'
                                              }"
                                              x-text="a.status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button type="button" @click="openEditAffiliate(a)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200">Edit</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="affiliates.length === 0 && !affiliatesLoading">
                                <td colspan="8" class="px-6 py-12 text-center text-slate-500 font-medium">No affiliates yet. Add one above.</td>
                            </tr>
                            <tr x-show="affiliatesLoading">
                                <td colspan="8" class="px-6 py-12 text-center"><div class="animate-spin w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full mx-auto"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add affiliate modal -->
    <div x-show="showAddAffiliate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60" @click="showAddAffiliate = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8" @click.stop>
            <h3 class="text-xl font-black text-slate-900 mb-6">Add affiliate</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">User</label>
                    <select x-model="addForm.user_id" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none">
                        <option value="">Select user</option>
                        <template x-for="u in eligibleUsers" :key="u.id">
                            <option :value="u.id" x-text="u.full_name + ' (' + (u.email || u.phone) + ')'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Referral code (optional)</label>
                    <input type="text" x-model="addForm.referral_code" placeholder="Leave blank to auto-generate" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Commission rate %</label>
                    <input type="number" step="0.01" min="0" max="100" x-model="addForm.commission_rate" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Status</label>
                    <select x-model="addForm.status" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <p x-show="addError" class="text-sm text-red-600 font-bold" x-text="addError"></p>
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showAddAffiliate = false" class="flex-1 py-3 rounded-xl font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="button" @click="submitAddAffiliate()" :disabled="addLoading" class="flex-1 py-3 rounded-xl font-bold btn-admin-primary disabled:opacity-50">Add</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit affiliate modal -->
    <div x-show="showEditAffiliate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60" @click="showEditAffiliate = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8" @click.stop>
            <h3 class="text-xl font-black text-slate-900 mb-6">Edit affiliate</h3>
            <p class="text-sm text-slate-600 mb-4" x-text="editAffiliate?.user?.full_name + ' (' + (editAffiliate?.referral_code || '') + ')'"></p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Status</label>
                    <select x-model="editForm.status" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Commission rate %</label>
                    <input type="number" step="0.01" min="0" max="100" x-model="editForm.commission_rate" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none">
                </div>
                <p x-show="editError" class="text-sm text-red-600 font-bold" x-text="editError"></p>
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showEditAffiliate = false" class="flex-1 py-3 rounded-xl font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="button" @click="submitEditAffiliate()" :disabled="editLoading" class="flex-1 py-3 rounded-xl font-bold btn-admin-primary disabled:opacity-50">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve withdrawal modal -->
    <div x-show="showApproveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60" @click="showApproveModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8" @click.stop>
            <h3 class="text-xl font-black text-slate-900 mb-2">Approve withdrawal</h3>
            <p class="text-slate-600 mb-4" x-text="selectedWithdrawal ? (selectedWithdrawal.affiliate?.user?.full_name + ' – GH₵ ' + (selectedWithdrawal.amount != null ? Number(selectedWithdrawal.amount).toFixed(2) : '0.00')) : ''"></p>
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-700 mb-1">Admin note (optional)</label>
                <textarea x-model="withdrawalNote" rows="2" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="showApproveModal = false" class="flex-1 py-3 rounded-xl font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="confirmApprove()" :disabled="withdrawalActionLoading" class="flex-1 py-3 rounded-xl font-bold bg-green-600 text-white hover:bg-green-700 disabled:opacity-50">Approve</button>
            </div>
        </div>
    </div>

    <!-- Reject withdrawal modal -->
    <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60" @click="showRejectModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8" @click.stop>
            <h3 class="text-xl font-black text-slate-900 mb-2">Reject withdrawal</h3>
            <p class="text-slate-600 mb-4" x-text="selectedWithdrawal ? (selectedWithdrawal.affiliate?.user?.full_name + ' – GH₵ ' + (selectedWithdrawal.amount != null ? Number(selectedWithdrawal.amount).toFixed(2) : '0.00')) : ''"></p>
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-700 mb-1">Reason / note (optional)</label>
                <textarea x-model="withdrawalNote" rows="2" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="showRejectModal = false" class="flex-1 py-3 rounded-xl font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="confirmReject()" :disabled="withdrawalActionLoading" class="flex-1 py-3 rounded-xl font-bold bg-red-600 text-white hover:bg-red-700 disabled:opacity-50">Reject</button>
            </div>
        </div>
    </div>

    <!-- View withdrawal detail modal (referred users + purchases that earned the amount) -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60" @click="showViewModal = false; selectedReferredUser = null"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col" @click.stop>
            <div class="p-6 border-b border-slate-100 flex-shrink-0">
                <h3 class="text-xl font-black text-slate-900">Withdrawal detail</h3>
                <p class="text-slate-600 mt-1" x-text="viewDetail?.withdrawal?.affiliate?.user ? (viewDetail.withdrawal.affiliate.user.full_name + ' – GH₵ ' + (viewDetail.withdrawal.amount != null ? Number(viewDetail.withdrawal.amount).toFixed(2) : '0.00')) : ''"></p>
            </div>
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div x-show="viewDetailLoading" class="text-center py-8">
                    <div class="animate-spin w-10 h-10 border-4 border-primary-600 border-t-transparent rounded-full mx-auto"></div>
                    <p class="text-slate-500 font-medium mt-2">Loading…</p>
                </div>
                <template x-if="viewDetail && !viewDetailLoading">
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-sm font-black text-slate-500 uppercase tracking-wider mb-2">Referred users</h4>
                            <p class="text-slate-500 text-xs mb-2">Use <strong>View</strong> to see each user’s total deposits and total spent.</p>
                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 font-bold text-slate-600">Name</th>
                                            <th class="px-4 py-3 font-bold text-slate-600">Email</th>
                                            <th class="px-4 py-3 font-bold text-slate-600">Phone</th>
                                            <th class="px-4 py-3 font-bold text-slate-600">Referred</th>
                                            <th class="px-4 py-3 font-bold text-slate-600 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="u in (viewDetail.referred_users || [])" :key="u.id">
                                            <tr class="hover:bg-slate-50/50">
                                                <td class="px-4 py-3 font-medium text-slate-800" x-text="u.full_name || '—'"></td>
                                                <td class="px-4 py-3 text-slate-600" x-text="u.email || '—'"></td>
                                                <td class="px-4 py-3 text-slate-600" x-text="u.phone || '—'"></td>
                                                <td class="px-4 py-3 text-slate-500 text-xs" x-text="u.created_at ? new Date(u.created_at).toLocaleString() : '—'"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="selectedReferredUser = u" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-primary-100 text-primary-700 hover:bg-primary-200 border border-primary-200">View</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <p x-show="!(viewDetail.referred_users || []).length" class="text-slate-500 font-medium py-4">No referred users.</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-500 uppercase tracking-wider mb-2">Purchases that earned commission</h4>
                            <p class="text-slate-600 text-sm mb-2" x-text="'Commission rate: ' + (viewDetail.commission_rate != null ? Number(viewDetail.commission_rate) : 0) + '%. Total earned from purchases: GH₵ ' + (viewDetail.total_earned_from_purchases != null ? Number(viewDetail.total_earned_from_purchases).toFixed(2) : '0.00')"></p>
                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 font-bold text-slate-600">Date</th>
                                            <th class="px-4 py-3 font-bold text-slate-600">Reference</th>
                                            <th class="px-4 py-3 font-bold text-slate-600">Referred user</th>
                                            <th class="px-4 py-3 font-bold text-slate-600">Beneficiary</th>
                                            <th class="px-4 py-3 font-bold text-slate-600 text-right">Amount</th>
                                            <th class="px-4 py-3 font-bold text-slate-600 text-right">Earned</th>
                                            <th class="px-4 py-3 font-bold text-slate-600">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="p in (viewDetail.earning_purchases || [])" :key="p.id">
                                            <tr class="hover:bg-slate-50/50">
                                                <td class="px-4 py-3 text-slate-600 text-xs" x-text="p.created_at ? new Date(p.created_at).toLocaleString() : '—'"></td>
                                                <td class="px-4 py-3 font-mono text-slate-700" x-text="p.reference || '—'"></td>
                                                <td class="px-4 py-3 font-medium text-slate-800" x-text="p.user ? (p.user.full_name || p.user.email || '—') : '—'"></td>
                                                <td class="px-4 py-3 text-slate-600" x-text="p.beneficiary || '—'"></td>
                                                <td class="px-4 py-3 text-right font-medium text-slate-800">GH₵ <span x-text="Number(p.amount).toFixed(2)"></span></td>
                                                <td class="px-4 py-3 text-right font-bold text-green-600">GH₵ <span x-text="Number(p.commission).toFixed(2)"></span></td>
                                                <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded text-xs font-bold uppercase" :class="{ 'bg-green-100 text-green-700': p.status === 'delivered' || p.status === 'processing', 'bg-orange-100 text-orange-700': p.status === 'pending', 'bg-red-100 text-red-700': p.status === 'failed' }" x-text="p.status || '—'"></span></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <p x-show="!(viewDetail.earning_purchases || []).length" class="text-slate-500 font-medium py-4">No purchases with credited commission yet.</p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="p-6 border-t border-slate-100 flex-shrink-0">
                <button type="button" @click="showViewModal = false; selectedReferredUser = null" class="w-full py-3 rounded-xl font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>

    <!-- Referred user detail modal (total deposits, total spent) -->
    <div x-show="selectedReferredUser" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60" @click="selectedReferredUser = null"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 border border-slate-200" @click.stop>
            <h4 class="text-lg font-black text-slate-900 mb-4">Referred user</h4>
            <template x-if="selectedReferredUser">
                <div class="space-y-3 text-sm">
                    <p class="font-bold text-slate-700"><span class="text-slate-500">Name:</span> <span x-text="selectedReferredUser.full_name || '—'"></span></p>
                    <p class="font-bold text-slate-700"><span class="text-slate-500">Email:</span> <span x-text="selectedReferredUser.email || '—'"></span></p>
                    <p class="font-bold text-slate-700"><span class="text-slate-500">Phone:</span> <span x-text="selectedReferredUser.phone || '—'"></span></p>
                    <p class="font-bold text-slate-700"><span class="text-slate-500">Referred:</span> <span x-text="selectedReferredUser.created_at ? new Date(selectedReferredUser.created_at).toLocaleString() : '—'"></span></p>
                    <div class="pt-3 mt-3 border-t border-slate-200">
                        <p class="font-black text-green-700"><span class="text-slate-500 font-bold">Total deposits:</span> GH₵ <span x-text="selectedReferredUser.total_deposits != null ? Number(selectedReferredUser.total_deposits).toFixed(2) : '0.00'"></span></p>
                        <p class="font-black text-slate-800"><span class="text-slate-500 font-bold">Total spent:</span> GH₵ <span x-text="selectedReferredUser.total_spent != null ? Number(selectedReferredUser.total_spent).toFixed(2) : '0.00'"></span></p>
                    </div>
                </div>
            </template>
            <div class="mt-6">
                <button type="button" @click="selectedReferredUser = null" class="w-full py-2.5 rounded-xl font-bold border border-slate-200 text-slate-600 hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function affiliateManagement() {
    return {
        affiliates: [],
        affiliatesLoading: true,
        affiliateStatusFilter: '',
        affiliateSearch: '',
        withdrawals: [],
        withdrawalsLoading: true,
        withdrawalStatusFilter: 'pending',
        withdrawalSearch: '',
        eligibleUsers: [],
        showAddAffiliate: false,
        addForm: { user_id: '', referral_code: '', commission_rate: 5, status: 'pending' },
        addError: '',
        addLoading: false,
        showEditAffiliate: false,
        editAffiliate: null,
        editForm: { status: 'approved', commission_rate: 5 },
        editError: '',
        editLoading: false,
        showApproveModal: false,
        showRejectModal: false,
        showViewModal: false,
        viewDetail: null,
        viewDetailLoading: false,
        selectedReferredUser: null,
        selectedWithdrawal: null,
        withdrawalNote: '',
        withdrawalActionLoading: false,

        async init() {
            if (!this.$store.auth.token) { window.location.href = '/login'; return; }
            await Promise.all([this.fetchAffiliates(), this.fetchWithdrawals()]);
        },

        async loadEligibleUsers() {
            try {
                const r = await fetch('{{ url("/api/admin/affiliates/eligible-users") }}', { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (r.ok) this.eligibleUsers = await r.json();
            } catch (e) {}
        },

        async fetchAffiliates() {
            this.affiliatesLoading = true;
            try {
                const params = new URLSearchParams();
                if (this.affiliateSearch && this.affiliateSearch.trim()) params.set('search', this.affiliateSearch.trim());
                if (this.affiliateStatusFilter) params.set('status', this.affiliateStatusFilter);
                const url = '{{ url("/api/admin/affiliates") }}' + (params.toString() ? '?' + params.toString() : '');
                const r = await fetch(url, { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (r.ok) {
                    const d = await r.json();
                    this.affiliates = d.data || d;
                }
            } catch (e) {}
            this.affiliatesLoading = false;
        },

        async fetchWithdrawals() {
            this.withdrawalsLoading = true;
            try {
                const params = new URLSearchParams();
                if (this.withdrawalSearch && this.withdrawalSearch.trim()) params.set('search', this.withdrawalSearch.trim());
                if (this.withdrawalStatusFilter) params.set('status', this.withdrawalStatusFilter);
                const url = '{{ url("/api/admin/affiliate-withdrawals") }}' + (params.toString() ? '?' + params.toString() : '');
                const r = await fetch(url, { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (r.ok) {
                    const d = await r.json();
                    this.withdrawals = d.data || d;
                }
            } catch (e) {}
            this.withdrawalsLoading = false;
        },

        async exportAffiliatesCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.affiliateSearch && this.affiliateSearch.trim()) params.set('search', this.affiliateSearch.trim());
                if (this.affiliateStatusFilter) params.set('status', this.affiliateStatusFilter);
                const r = await fetch('{{ url("/api/admin/affiliates") }}?' + params.toString(), { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (!r.ok) throw new Error('Export failed');
                const d = await r.json();
                const list = d.data || d || [];
                if (!list.length) { alert('No affiliates to export.'); return; }
                const headers = ['ID', 'User', 'Email', 'Referral Code', 'Commission %', 'Balance', 'Total Earned', 'Total Withdrawn', 'Status'];
                const escape = (v) => '"' + String(v).replace(/"/g, '""') + '"';
                const rows = list.map(a => [a.id, (a.user?.full_name || '').replace(/,/g, ' '), (a.user?.email || '').replace(/,/g, ' '), a.referral_code || '', a.commission_rate != null ? a.commission_rate : '', a.balance != null ? a.balance : '', a.total_earned != null ? a.total_earned : '', a.total_withdrawn != null ? a.total_withdrawn : '', a.status || '']);
                const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(r => r.map(escape).join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", "affiliates_export_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) { console.error(e); alert('Export failed.'); }
        },

        async exportWithdrawalsCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.withdrawalSearch && this.withdrawalSearch.trim()) params.set('search', this.withdrawalSearch.trim());
                if (this.withdrawalStatusFilter) params.set('status', this.withdrawalStatusFilter);
                const r = await fetch('{{ url("/api/admin/affiliate-withdrawals") }}?' + params.toString(), { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (!r.ok) throw new Error('Export failed');
                const d = await r.json();
                const list = d.data || d || [];
                if (!list.length) { alert('No withdrawals to export.'); return; }
                const headers = ['ID', 'Affiliate', 'Email', 'Amount', 'Status', 'Requested At'];
                const escape = (v) => '"' + String(v).replace(/"/g, '""') + '"';
                const rows = list.map(w => [w.id, (w.affiliate?.user?.full_name || '').replace(/,/g, ' '), (w.affiliate?.user?.email || '').replace(/,/g, ' '), w.amount != null ? w.amount : '', w.status || '', w.created_at ? new Date(w.created_at).toLocaleString() : '']);
                const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(r => r.map(escape).join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", "affiliate_withdrawals_export_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) { console.error(e); alert('Export failed.'); }
        },

        async submitAddAffiliate() {
            this.addError = '';
            if (!this.addForm.user_id) { this.addError = 'Select a user.'; return; }
            this.addLoading = true;
            try {
                const r = await fetch('{{ url("/api/admin/affiliates") }}', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        user_id: parseInt(this.addForm.user_id, 10),
                        referral_code: this.addForm.referral_code || undefined,
                        commission_rate: parseFloat(this.addForm.commission_rate) || 5,
                        status: this.addForm.status || 'pending'
                    })
                });
                const d = await r.json().catch(() => ({}));
                if (r.ok) {
                    this.showAddAffiliate = false;
                    this.addForm = { user_id: '', referral_code: '', commission_rate: 5, status: 'pending' };
                    await this.fetchAffiliates();
                    await this.loadEligibleUsers();
                } else this.addError = d.error || 'Failed to add affiliate.';
            } catch (e) { this.addError = 'Network error.'; }
            this.addLoading = false;
        },

        openEditAffiliate(a) {
            this.editAffiliate = a;
            this.editForm = { status: a.status, commission_rate: parseFloat(a.commission_rate) || 5 };
            this.editError = '';
            this.showEditAffiliate = true;
        },

        async submitEditAffiliate() {
            if (!this.editAffiliate) return;
            this.editError = '';
            this.editLoading = true;
            try {
                const r = await fetch('{{ url("/api/admin/affiliates") }}/' + this.editAffiliate.id, {
                    method: 'PUT',
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.editForm)
                });
                const d = await r.json().catch(() => ({}));
                if (r.ok) {
                    this.showEditAffiliate = false;
                    this.editAffiliate = null;
                    await this.fetchAffiliates();
                } else this.editError = d.error || 'Failed to update.';
            } catch (e) { this.editError = 'Network error.'; }
            this.editLoading = false;
        },

        async openViewWithdrawal(w) {
            this.viewDetail = null;
            this.showViewModal = true;
            this.viewDetailLoading = true;
            try {
                const r = await fetch('{{ url("/api/admin/affiliate-withdrawals") }}/' + w.id + '/detail', { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (r.ok) this.viewDetail = await r.json();
                else this.viewDetail = { referred_users: [], earning_purchases: [], total_earned_from_purchases: 0 };
            } catch (e) {
                this.viewDetail = { referred_users: [], earning_purchases: [], total_earned_from_purchases: 0 };
            }
            this.viewDetailLoading = false;
        },

        openApprove(w) {
            this.selectedWithdrawal = w;
            this.withdrawalNote = '';
            this.showApproveModal = true;
        },

        openReject(w) {
            this.selectedWithdrawal = w;
            this.withdrawalNote = '';
            this.showRejectModal = true;
        },

        async confirmApprove() {
            if (!this.selectedWithdrawal) return;
            this.withdrawalActionLoading = true;
            try {
                const r = await fetch('{{ url("/api/admin/affiliate-withdrawals") }}/' + this.selectedWithdrawal.id + '/approve', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ admin_notes: this.withdrawalNote })
                });
                if (r.ok) {
                    this.showApproveModal = false;
                    this.selectedWithdrawal = null;
                    await this.fetchWithdrawals();
                    await this.fetchAffiliates();
                } else {
                    const d = await r.json().catch(() => ({}));
                    alert(d.error || 'Failed to approve.');
                }
            } catch (e) { alert('Network error.'); }
            this.withdrawalActionLoading = false;
        },

        async confirmReject() {
            if (!this.selectedWithdrawal) return;
            this.withdrawalActionLoading = true;
            try {
                const r = await fetch('{{ url("/api/admin/affiliate-withdrawals") }}/' + this.selectedWithdrawal.id + '/reject', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ admin_notes: this.withdrawalNote })
                });
                if (r.ok) {
                    this.showRejectModal = false;
                    this.selectedWithdrawal = null;
                    await this.fetchWithdrawals();
                } else {
                    const d = await r.json().catch(() => ({}));
                    alert(d.error || 'Failed to reject.');
                }
            } catch (e) { alert('Network error.'); }
            this.withdrawalActionLoading = false;
        }
    };
}
</script>
@endsection
