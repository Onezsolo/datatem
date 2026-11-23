@extends('layouts.app')

@section('content')
<div class="admin-page min-h-screen bg-gray-50 py-8" x-data="resellerManagement()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <a href="/admin/dashboard" class="admin-back mb-2 inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Resellers</p>
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Resellers</h1>
                <p class="text-slate-500 mt-1 text-sm">Applications and reseller payout requests.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button @click="exportCSV()" class="btn-admin-secondary px-5 py-2.5 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </button>
                <button @click="fetchResellers()" class="admin-back font-semibold flex items-center text-sm">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-8">
            <button type="button" @click="activeTab = 'applications'; $nextTick(() => { if (typeof window !== 'undefined') history.replaceState(null, '', '/admin/resellers'); })"
                    :class="activeTab === 'applications' ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-slate-600 border-slate-200'"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold border transition-colors">Applications</button>
            <button type="button" @click="activeTab = 'withdrawals'; fetchWithdrawals(1); if (typeof window !== 'undefined') history.replaceState(null, '', '/admin/resellers#withdrawals')"
                    :class="activeTab === 'withdrawals' ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-slate-600 border-slate-200'"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold border transition-colors">Payout requests</button>
        </div>

        <div class="admin-card overflow-hidden" x-show="activeTab === 'applications'" x-cloak>
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center gap-4">
                <div class="relative flex-1 max-w-md flex gap-2">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" @keydown.enter="fetchResellers(1)" placeholder="Search (name, email)..." class="w-full pl-12 pr-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-cyan-500 outline-none text-slate-900">
                    <button type="button" @click="fetchResellers(1)" class="btn-admin-primary px-5 py-3 shrink-0 text-sm">Search</button>
                </div>
                <select x-model="statusFilter" @change="fetchResellers(1)" class="px-4 py-3 rounded-xl bg-white border border-slate-200 outline-none focus:border-cyan-500 font-medium text-slate-600 text-sm">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="overflow-x-auto min-h-[400px]">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest">User</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest">Message</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Sales</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Profit</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest">Reviewed</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="item in requests" :key="item.id">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-8 py-6">
                                    <p class="font-black text-slate-900" x-text="item.user?.full_name || '—'"></p>
                                    <p class="text-xs font-bold text-slate-500" x-text="item.user?.email || ''"></p>
                                </td>
                                <td class="px-8 py-6 max-w-xs">
                                    <p class="text-sm font-medium text-slate-600 truncate max-w-[280px]" :title="item.message || ''" x-text="item.message || '—'"></p>
                                    <p x-show="item.admin_notes" class="text-xs text-slate-400 mt-1" x-text="'Note: ' + (item.admin_notes || '')"></p>
                                    <button type="button" @click="viewRequest(item)" class="mt-2 text-xs font-bold text-cyan-600 hover:underline font-semibold text-sm">View full</button>
                                </td>
                                <td class="px-8 py-6 text-right font-bold text-slate-700" x-text="'GH₵ ' + (item.total_sales != null ? Number(item.total_sales).toFixed(2) : '0.00')"></td>
                                <td class="px-8 py-6 text-right font-bold text-emerald-700" x-text="'GH₵ ' + (item.total_profit != null ? Number(item.total_profit).toFixed(2) : '0.00')"></td>
                                <td class="px-8 py-6 text-center">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border"
                                          :class="{
                                              'bg-amber-50 text-amber-700 border-amber-100': item.status === 'pending',
                                              'bg-green-50 text-green-700 border-green-100': item.status === 'approved',
                                              'bg-red-50 text-red-700 border-red-100': item.status === 'rejected'
                                          }"
                                          x-text="item.status"></span>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-sm font-bold text-slate-500" x-text="item.reviewed_at ? new Date(item.reviewed_at).toLocaleString() : '—'"></p>
                                    <p x-show="item.reviewer" class="text-xs text-slate-400" x-text="item.reviewer?.full_name || ''"></p>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="viewRequest(item)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 transition-colors">View</button>
                                        <template x-if="item.status === 'pending'">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                        @click="approve(item)"
                                                        :disabled="actionId === item.id"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-green-100 text-green-700 border border-green-200 hover:bg-green-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                    <span x-show="actionId !== item.id">Approve</span>
                                                    <span x-show="actionId === item.id">…</span>
                                                </button>
                                                <button type="button"
                                                        @click="reject(item)"
                                                        :disabled="actionId === item.id"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-700 border border-red-200 hover:bg-red-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                    Reject
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="requests.length === 0">
                            <td colspan="7" class="px-8 py-20 text-center">
                                <p class="text-slate-400 font-bold">No reseller requests found.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-8 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm font-bold text-slate-500">
                    Showing <span x-text="pagination.total ? ((pagination.current_page - 1) * pagination.per_page + 1) : 0"></span>–<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of <span x-text="pagination.total"></span> request(s)
                </p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-4 py-2 rounded-xl font-bold border border-slate-200 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50">Previous</button>
                    <span class="px-3 py-1 text-sm font-bold text-slate-600" x-text="'Page ' + pagination.current_page + ' of ' + (pagination.last_page || 1)"></span>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-4 py-2 rounded-xl font-bold border border-slate-200 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50">Next</button>
                    <button @click="fetchResellers()" class="p-2 text-slate-400 hover:text-cyan-600 rounded-xl" title="Refresh"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>
                </div>
            </div>
        </div>

        <!-- Reseller payout withdrawals -->
        <div class="admin-card overflow-hidden" x-show="activeTab === 'withdrawals'" x-cloak>
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center gap-4">
                <div class="relative flex-1 max-w-md flex gap-2">
                    <input type="text" x-model="withdrawalSearch" @keydown.enter="fetchWithdrawals(1)" placeholder="Search (name, email)..." class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-cyan-500 outline-none text-slate-900">
                    <button type="button" @click="fetchWithdrawals(1)" class="btn-admin-primary px-5 py-3 shrink-0 text-sm">Search</button>
                </div>
                <select x-model="withdrawalStatusFilter" @change="fetchWithdrawals(1)" class="px-4 py-3 rounded-xl bg-white border border-slate-200 outline-none focus:border-cyan-500 font-medium text-slate-600 text-sm">
                    <option value="all">All status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button type="button" @click="exportWithdrawalsCSV()" class="btn-admin-secondary px-5 py-2.5 text-sm">Export CSV</button>
                <button type="button" @click="fetchWithdrawals()" class="admin-back font-semibold text-sm">Refresh</button>
            </div>
            <div class="overflow-x-auto min-h-[400px]">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest">Reseller</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest">MoMo</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Amount</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest">Requested</th>
                            <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="w in withdrawals" :key="w.id">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-8 py-6">
                                    <p class="font-black text-slate-900" x-text="w.user?.full_name || '—'"></p>
                                    <p class="text-xs font-bold text-slate-500" x-text="w.user?.email || ''"></p>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="font-mono text-sm font-bold text-slate-800" x-text="w.momo_number || '—'"></p>
                                </td>
                                <td class="px-8 py-6 text-right font-bold text-slate-800" x-text="'GHS ' + (w.amount != null ? Number(w.amount).toFixed(2) : '0.00')"></td>
                                <td class="px-8 py-6 text-center">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border"
                                          :class="{
                                              'bg-amber-50 text-amber-700 border-amber-100': w.status === 'pending',
                                              'bg-green-50 text-green-700 border-green-100': w.status === 'approved',
                                              'bg-red-50 text-red-700 border-red-100': w.status === 'rejected'
                                          }"
                                          x-text="w.status"></span>
                                </td>
                                <td class="px-8 py-6 text-sm font-bold text-slate-500" x-text="w.created_at ? new Date(w.created_at).toLocaleString() : '—'"></td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <button type="button" @click="openWithdrawalDetail(w)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200">View</button>
                                        <template x-if="w.status === 'pending'">
                                            <span class="inline-flex gap-2">
                                                <button type="button" @click="openApproveWithdrawal(w)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-green-100 text-green-700 border border-green-200 hover:bg-green-200">Approve</button>
                                                <button type="button" @click="openRejectWithdrawal(w)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-700 border border-red-200 hover:bg-red-200">Reject</button>
                                            </span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="withdrawals.length === 0 && !withdrawalsLoading">
                            <td colspan="6" class="px-8 py-20 text-center text-slate-400 font-bold">No payout requests.</td>
                        </tr>
                        <tr x-show="withdrawalsLoading">
                            <td colspan="6" class="px-8 py-20 text-center text-slate-400 font-bold">Loading…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-8 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm font-bold text-slate-500">
                    Withdrawals: <span x-text="withdrawalPagination.total"></span> total
                </p>
                <div class="flex items-center gap-2">
                    <button @click="goToWithdrawalPage(withdrawalPagination.current_page - 1)" :disabled="withdrawalPagination.current_page <= 1" class="px-4 py-2 rounded-xl font-bold border border-slate-200 disabled:opacity-50">Previous</button>
                    <span class="px-3 py-1 text-sm font-bold text-slate-600" x-text="'Page ' + withdrawalPagination.current_page + ' / ' + (withdrawalPagination.last_page || 1)"></span>
                    <button @click="goToWithdrawalPage(withdrawalPagination.current_page + 1)" :disabled="withdrawalPagination.current_page >= withdrawalPagination.last_page" class="px-4 py-2 rounded-xl font-bold border border-slate-200 disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View request modal -->
    <div x-show="viewOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click="viewOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div x-show="viewOpen"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full max-h-[90vh] overflow-hidden">
                <div class="p-8 border-b border-slate-100">
                    <h3 class="text-xl font-black text-slate-900">Reseller request</h3>
                    <p class="text-sm font-bold text-slate-500 mt-1" x-text="selectedRequest?.user?.full_name || '—'"></p>
                    <p class="text-xs text-slate-400" x-text="selectedRequest?.user?.email || ''"></p>
                </div>
                <div class="p-8 overflow-y-auto max-h-[50vh]">
                    <div class="mb-6">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Message</p>
                        <p class="text-slate-700 font-medium whitespace-pre-wrap break-words" x-text="selectedRequest?.message || 'No message provided.'"></p>
                    </div>
                    <template x-if="selectedRequest?.admin_notes">
                        <div class="mb-6">
                            <p class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Admin note</p>
                            <p class="text-slate-600 text-sm whitespace-pre-wrap break-words" x-text="selectedRequest?.admin_notes"></p>
                        </div>
                    </template>
                    <div class="mb-6">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Storefront link</p>
                        <template x-if="selectedRequest?.user?.store_slug || selectedRequest?.user?.reseller_slug">
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="text-slate-700 font-mono text-sm break-all" x-text="'/store/' + (selectedRequest?.user?.store_slug || selectedRequest?.user?.reseller_slug || '')"></p>
                                <a :href="'/store/' + (selectedRequest?.user?.store_slug || selectedRequest?.user?.reseller_slug || '')"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold bg-cyan-100 text-cyan-700 border border-cyan-200 hover:bg-cyan-200 transition-colors shrink-0">
                                    Open store →
                                </a>
                            </div>
                        </template>
                        <template x-if="!selectedRequest?.user?.store_slug && !selectedRequest?.user?.reseller_slug">
                            <p class="text-slate-500 text-sm font-medium">Store not set up yet.</p>
                        </template>
                    </div>
                    <div class="mb-6 flex flex-wrap gap-4">
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-wider mb-1">Total sales</p>
                            <p class="text-lg font-black text-slate-800" x-text="'GH₵ ' + (selectedRequest?.total_sales != null ? Number(selectedRequest.total_sales).toFixed(2) : '0.00')"></p>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-wider mb-1">Total profit</p>
                            <p class="text-lg font-black text-emerald-700" x-text="'GH₵ ' + (selectedRequest?.total_profit != null ? Number(selectedRequest.total_profit).toFixed(2) : '0.00')"></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <span class="px-3 py-1 rounded-full font-bold text-slate-600 bg-slate-100" x-text="'Status: ' + (selectedRequest?.status || '—')"></span>
                        <span class="px-3 py-1 rounded-full font-bold text-slate-500 bg-slate-50" x-text="selectedRequest?.reviewed_at ? 'Reviewed: ' + new Date(selectedRequest.reviewed_at).toLocaleString() : ''"></span>
                        <span class="px-3 py-1 rounded-full font-bold text-slate-500 bg-slate-50" x-text="selectedRequest?.reviewer?.full_name ? 'By: ' + selectedRequest.reviewer.full_name : ''"></span>
                    </div>
                </div>
                <div class="p-8 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @click="viewOpen = false" class="px-5 py-2.5 rounded-xl font-bold border border-slate-200 text-slate-600 hover:bg-slate-100">Close</button>
                    <template x-if="selectedRequest?.status === 'pending'">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="approve(selectedRequest)" :disabled="actionId === selectedRequest?.id" class="px-5 py-2.5 rounded-xl font-bold bg-green-100 text-green-700 border border-green-200 hover:bg-green-200 disabled:opacity-50">Approve</button>
                            <button type="button" @click="reject(selectedRequest)" :disabled="actionId === selectedRequest?.id" class="px-5 py-2.5 rounded-xl font-bold bg-red-100 text-red-700 border border-red-200 hover:bg-red-200 disabled:opacity-50">Reject</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdrawal detail -->
    <div x-show="withdrawalDetailOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click="withdrawalDetailOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div @click.stop class="relative bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-100 shrink-0">
                    <h3 class="text-xl font-black text-slate-900">Payout request detail</h3>
                    <p class="text-sm text-slate-500 mt-1" x-text="withdrawalDetail?.withdrawal?.user ? (withdrawalDetail.withdrawal.user.full_name + ' — GHS ' + Number(withdrawalDetail.withdrawal.amount || 0).toFixed(2)) : ''"></p>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <template x-if="withdrawalDetail">
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase mb-2">Payout MoMo number</p>
                            <p class="font-mono font-bold text-slate-900 mb-4" x-text="withdrawalDetail.withdrawal?.momo_number || '—'"></p>
                            <p class="text-xs font-black text-slate-400 uppercase mb-2">Wallet balance (now)</p>
                            <p class="font-bold text-slate-800 mb-2" x-text="'GHS ' + Number(withdrawalDetail.withdrawal?.user?.balance ?? 0).toFixed(2)"></p>
                            <p class="text-xs font-black text-slate-400 uppercase mb-2">Withdrawable profit (tracked)</p>
                            <p class="font-bold text-emerald-700 mb-4" x-text="'GHS ' + Number(withdrawalDetail.withdrawal?.user?.reseller_withdrawable_balance ?? 0).toFixed(2)"></p>
                            <p class="text-xs font-black text-slate-400 uppercase mb-2">Total credited profit (store sales)</p>
                            <p class="font-bold text-emerald-700 mb-4" x-text="'GHS ' + Number(withdrawalDetail.total_credited_profit ?? 0).toFixed(2)"></p>
                            <p class="text-xs font-black text-slate-400 uppercase mb-2">Recent credited sales</p>
                            <div class="max-h-48 overflow-y-auto border border-slate-100 rounded-xl text-sm">
                                <template x-for="row in (withdrawalDetail.credited_sales || [])" :key="row.id">
                                    <div class="px-3 py-2 border-b border-slate-50 flex justify-between gap-2">
                                        <span class="font-mono text-xs" x-text="row.reference"></span>
                                        <span class="text-emerald-600 font-bold" x-text="'GHS ' + Number(row.reseller_profit).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="p-6 border-t border-slate-100 bg-slate-50/50 shrink-0 flex justify-end">
                    <button type="button" @click="withdrawalDetailOpen = false" class="px-5 py-2.5 rounded-xl font-bold border border-slate-200">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve withdrawal -->
    <div x-show="withdrawalApproveOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click="withdrawalApproveOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div @click.stop class="relative bg-white rounded-3xl shadow-2xl border max-w-md w-full p-8">
                <h3 class="text-xl font-black text-slate-900 mb-2">Approve payout</h3>
                <p class="text-sm text-slate-500 mb-2">Amount will be deducted from the reseller wallet balance.</p>
                <p class="text-sm font-mono font-bold text-slate-800 mb-4" x-show="selectedWithdrawal?.momo_number">MoMo: <span x-text="selectedWithdrawal?.momo_number"></span></p>
                <textarea x-model="withdrawalNote" rows="2" placeholder="Optional note" class="w-full px-4 py-3 rounded-xl border border-slate-200 outline-none mb-4"></textarea>
                <div class="flex gap-2">
                    <button type="button" @click="withdrawalApproveOpen = false" class="flex-1 py-3 rounded-xl font-bold border border-slate-200">Cancel</button>
                    <button type="button" @click="confirmApproveWithdrawal()" :disabled="withdrawalActionLoading" class="flex-1 py-3 rounded-xl font-bold bg-green-600 text-white disabled:opacity-50">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject withdrawal -->
    <div x-show="withdrawalRejectOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click="withdrawalRejectOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div @click.stop class="relative bg-white rounded-3xl shadow-2xl border max-w-md w-full p-8">
                <h3 class="text-xl font-black text-slate-900 mb-2">Reject payout</h3>
                <textarea x-model="withdrawalNote" rows="2" placeholder="Reason (optional, shown to user)" class="w-full px-4 py-3 rounded-xl border border-slate-200 outline-none mb-4"></textarea>
                <div class="flex gap-2">
                    <button type="button" @click="withdrawalRejectOpen = false" class="flex-1 py-3 rounded-xl font-bold border border-slate-200">Cancel</button>
                    <button type="button" @click="confirmRejectWithdrawal()" :disabled="withdrawalActionLoading" class="flex-1 py-3 rounded-xl font-bold bg-red-600 text-white disabled:opacity-50">Reject</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resellerManagement() {
    return {
        activeTab: 'applications',
        requests: [],
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
        search: '',
        statusFilter: 'all',
        sortField: 'created_at',
        sortOrder: 'desc',
        actionId: null,
        viewOpen: false,
        selectedRequest: null,
        withdrawals: [],
        withdrawalsLoading: false,
        withdrawalSearch: '',
        withdrawalStatusFilter: 'pending',
        withdrawalPagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
        withdrawalDetailOpen: false,
        withdrawalDetail: null,
        withdrawalApproveOpen: false,
        withdrawalRejectOpen: false,
        selectedWithdrawal: null,
        withdrawalNote: '',
        withdrawalActionLoading: false,

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            if (typeof window !== 'undefined' && window.location.hash === '#withdrawals') {
                this.activeTab = 'withdrawals';
                await this.fetchWithdrawals(1);
            }
            await this.fetchResellers();
        },

        buildResellersParams(page) {
            const p = new URLSearchParams();
            if (page != null) p.set('page', page);
            p.set('per_page', this.pagination.per_page);
            if (this.search && this.search.trim()) p.set('search', this.search.trim());
            if (this.statusFilter && this.statusFilter !== 'all') p.set('status', this.statusFilter);
            p.set('sort_by', this.sortField);
            p.set('sort_dir', this.sortOrder);
            return p.toString();
        },
        async fetchResellers(page) {
            if (page != null) this.pagination.current_page = page;
            try {
                const res = await fetch('/api/admin/resellers?' + this.buildResellersParams(this.pagination.current_page), {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                this.requests = data.data !== undefined ? data.data : data;
                if (data.current_page != null) {
                    this.pagination.current_page = data.current_page;
                    this.pagination.last_page = data.last_page ?? 1;
                    this.pagination.total = data.total ?? this.requests.length;
                    this.pagination.per_page = data.per_page ?? 20;
                }
            } catch (e) {
                console.error(e);
            }
        },
        async exportCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.search && this.search.trim()) params.set('search', this.search.trim());
                if (this.statusFilter && this.statusFilter !== 'all') params.set('status', this.statusFilter);
                params.set('sort_by', this.sortField);
                params.set('sort_dir', this.sortOrder);
                const res = await fetch('/api/admin/resellers?' + params.toString(), { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Export failed');
                const data = await res.json();
                const list = data.data || data || [];
                if (!list.length) { (typeof showAlert === 'function' ? showAlert : alert)('No reseller requests to export.'); return; }
                const headers = ['ID', 'User', 'Email', 'Message', 'Total Sales', 'Total Profit', 'Status', 'Reviewed At'];
                const escape = (v) => '"' + String(v).replace(/"/g, '""') + '"';
                const rows = list.map(r => [r.id, (r.user?.full_name || '').replace(/,/g, ' '), (r.user?.email || '').replace(/,/g, ' '), (r.message || '').replace(/,/g, ' ').replace(/\n/g, ' '), r.total_sales != null ? r.total_sales : '', r.total_profit != null ? r.total_profit : '', r.status, r.reviewed_at ? new Date(r.reviewed_at).toLocaleString() : '']);
                const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(r => r.map(escape).join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", "resellers_export_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) { console.error(e); (typeof showAlert === 'function' ? showAlert : alert)('Export failed.'); }
        },
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.fetchResellers(page);
        },

        viewRequest(item) {
            this.selectedRequest = item;
            this.viewOpen = true;
        },

        async approve(item) {
            if (item.status !== 'pending' || this.actionId) return;
            this.actionId = item.id;
            try {
                const res = await fetch('/api/admin/resellers/' + item.id + '/approve', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ admin_notes: '' })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showAlert(data.error || 'Failed to approve.', 'Error', 'error');
                    return;
                }
                await this.fetchResellers();
                this.viewOpen = false;
            } catch (e) {
                console.error(e);
                showAlert('Failed to approve.', 'Error', 'error');
            } finally {
                this.actionId = null;
            }
        },

        async reject(item) {
            if (item.status !== 'pending' || this.actionId) return;
            const note = window.prompt('Optional reason for rejection (shown to user):');
            if (note === null) return; // user cancelled
            this.actionId = item.id;
            try {
                const res = await fetch('/api/admin/resellers/' + item.id + '/reject', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ admin_notes: note || '' })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showAlert(data.error || 'Failed to reject.', 'Error', 'error');
                    return;
                }
                await this.fetchResellers();
                this.viewOpen = false;
            } catch (e) {
                console.error(e);
                showAlert('Failed to reject.', 'Error', 'error');
            } finally {
                this.actionId = null;
            }
        },

        buildWithdrawalParams(page) {
            const p = new URLSearchParams();
            if (page != null) p.set('page', page);
            p.set('per_page', this.withdrawalPagination.per_page || 20);
            if (this.withdrawalSearch && this.withdrawalSearch.trim()) p.set('search', this.withdrawalSearch.trim());
            if (this.withdrawalStatusFilter && this.withdrawalStatusFilter !== 'all') p.set('status', this.withdrawalStatusFilter);
            p.set('sort_by', 'created_at');
            p.set('sort_dir', 'desc');
            return p.toString();
        },

        async fetchWithdrawals(page) {
            if (page != null) this.withdrawalPagination.current_page = page;
            this.withdrawalsLoading = true;
            try {
                const res = await fetch('/api/admin/reseller-withdrawals?' + this.buildWithdrawalParams(this.withdrawalPagination.current_page), {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    this.withdrawals = [];
                    return;
                }
                const data = await res.json();
                this.withdrawals = data.data !== undefined ? data.data : data;
                if (data.current_page != null) {
                    this.withdrawalPagination.current_page = data.current_page;
                    this.withdrawalPagination.last_page = data.last_page ?? 1;
                    this.withdrawalPagination.total = data.total ?? this.withdrawals.length;
                    this.withdrawalPagination.per_page = data.per_page ?? 20;
                }
            } catch (e) {
                console.error(e);
                this.withdrawals = [];
            } finally {
                this.withdrawalsLoading = false;
            }
        },

        goToWithdrawalPage(page) {
            if (page < 1 || page > this.withdrawalPagination.last_page) return;
            this.fetchWithdrawals(page);
        },

        async exportWithdrawalsCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.withdrawalSearch && this.withdrawalSearch.trim()) params.set('search', this.withdrawalSearch.trim());
                if (this.withdrawalStatusFilter && this.withdrawalStatusFilter !== 'all') params.set('status', this.withdrawalStatusFilter);
                const res = await fetch('/api/admin/reseller-withdrawals?' + params.toString(), { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Export failed');
                const data = await res.json();
                const list = data.data || [];
                if (!list.length) { (typeof showAlert === 'function' ? showAlert : alert)('No rows to export.'); return; }
                const headers = ['ID', 'User', 'Email', 'MoMo', 'Amount', 'Status', 'Created'];
                const escape = (v) => '"' + String(v).replace(/"/g, '""') + '"';
                const rows = list.map(w => [w.id, (w.user?.full_name || '').replace(/,/g, ' '), (w.user?.email || '').replace(/,/g, ' '), (w.momo_number || '').replace(/,/g, ' '), w.amount, w.status, w.created_at ? new Date(w.created_at).toLocaleString() : '']);
                const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(r => r.map(escape).join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", "reseller_withdrawals_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) { console.error(e); (typeof showAlert === 'function' ? showAlert : alert)('Export failed.'); }
        },

        async openWithdrawalDetail(w) {
            this.withdrawalDetail = null;
            this.withdrawalDetailOpen = true;
            try {
                const res = await fetch('/api/admin/reseller-withdrawals/' + w.id + '/detail', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                const d = await res.json().catch(() => ({}));
                if (res.ok) this.withdrawalDetail = d;
            } catch (e) { console.error(e); }
        },

        openApproveWithdrawal(w) {
            this.selectedWithdrawal = w;
            this.withdrawalNote = '';
            this.withdrawalApproveOpen = true;
        },

        openRejectWithdrawal(w) {
            this.selectedWithdrawal = w;
            this.withdrawalNote = '';
            this.withdrawalRejectOpen = true;
        },

        async confirmApproveWithdrawal() {
            if (!this.selectedWithdrawal) return;
            this.withdrawalActionLoading = true;
            try {
                const res = await fetch('/api/admin/reseller-withdrawals/' + this.selectedWithdrawal.id + '/approve', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ admin_notes: this.withdrawalNote || '' })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    (typeof showAlert === 'function' ? showAlert : alert)(data.error || 'Approve failed.');
                    return;
                }
                this.withdrawalApproveOpen = false;
                await this.fetchWithdrawals(this.withdrawalPagination.current_page);
            } catch (e) {
                console.error(e);
            } finally {
                this.withdrawalActionLoading = false;
            }
        },

        async confirmRejectWithdrawal() {
            if (!this.selectedWithdrawal) return;
            this.withdrawalActionLoading = true;
            try {
                const res = await fetch('/api/admin/reseller-withdrawals/' + this.selectedWithdrawal.id + '/reject', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ admin_notes: this.withdrawalNote || '' })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    (typeof showAlert === 'function' ? showAlert : alert)(data.error || 'Reject failed.');
                    return;
                }
                this.withdrawalRejectOpen = false;
                await this.fetchWithdrawals(this.withdrawalPagination.current_page);
            } catch (e) {
                console.error(e);
            } finally {
                this.withdrawalActionLoading = false;
            }
        }
    };
}
</script>
@endsection
