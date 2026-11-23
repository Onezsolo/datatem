@extends('layouts.app')

@section('content')
<div class="admin-page min-h-screen bg-gray-50 py-8" 
     x-data="userManagement()" 
     x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <a href="/admin/dashboard" class="admin-back mb-2 inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">User Management</p>
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Users</h1>
                <p class="text-slate-500 mt-1 text-sm">Manage community, balances, and permissions.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button @click="exportCSV()" class="btn-admin-secondary px-5 py-2.5 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </button>
                <button @click="openAddModal()" class="btn-admin-primary px-6 py-2.5 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add User
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="admin-card p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Users</p>
                <p class="text-xl font-bold text-slate-900" x-text="pagination.total"></p>
            </div>
            <div class="admin-card p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Admins</p>
                <p class="text-xl font-bold text-purple-600" x-text="users.filter(u => u.role === 'admin').length"></p>
            </div>
            <div class="admin-card p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Cumulative Balance</p>
                <p class="text-xl font-bold text-emerald-600">₵<span x-text="users.reduce((acc, u) => acc + parseFloat(u.balance), 0).toFixed(2)"></span></p>
            </div>
            <div class="admin-card p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">This page</p>
                <p class="text-xl font-bold text-cyan-600" x-text="users.length"></p>
            </div>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <div class="flex-1 relative flex gap-2">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" @keydown.enter="fetchUsers(1)" placeholder="Search users (name, email, phone)..." class="w-full pl-12 pr-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 outline-none transition-all text-slate-900">
                        <button type="button" @click="fetchUsers(1)" class="btn-admin-primary px-5 py-3 shrink-0 text-sm">Search</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <select x-model="roleFilter" @change="fetchUsers(1)" class="px-4 py-3 rounded-xl bg-white border border-slate-200 outline-none font-medium text-slate-700 focus:border-cyan-500 text-sm">
                            <option value="all">All Roles</option>
                            <option value="admin">Admins</option>
                            <option value="user">Users</option>
                        </select>
                        <button @click="fetchUsers()" class="p-3 border border-slate-200 rounded-xl text-slate-400 hover:text-cyan-600 hover:border-cyan-200 transition-all" title="Refresh">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto min-h-[400px]">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th @click="sortBy('full_name')" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-cyan-600">User <span x-show="sortField === 'full_name'" x-text="sortOrder === 'asc' ? '↑' : '↓'"></span></th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Role</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Balance</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="user in users" :key="user.id">
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div :class="user.role === 'admin' ? 'bg-purple-100 text-purple-600' : 'bg-cyan-100 text-cyan-600'" 
                                             class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm" x-text="user.full_name.charAt(0)"></div>
                                        <div>
                                            <p class="font-semibold text-slate-900" x-text="user.full_name"></p>
                                            <p class="text-xs text-slate-500" x-text="user.email"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="user.role === 'admin' ? 'bg-purple-50 text-purple-600 border-purple-100' : 'bg-slate-100 text-slate-600 border-slate-200'" 
                                          class="px-3 py-1 rounded-lg text-xs font-semibold uppercase border"
                                          x-text="user.role"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-900"><span class="text-slate-500 text-xs">GHS</span> <span x-text="parseFloat(user.balance).toLocaleString(undefined, {minimumFractionDigits: 2})"></span></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 text-xs font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Actions Dropdown Simulation with subtle buttons -->
                                        <button @click="openWalletModal(user, 'topup')" class="p-2.5 text-slate-400 hover:text-green-600 hover:bg-green-50 rounded-xl transition-all" title="Topup Wallet">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"/></svg>
                                        </button>
                                        <button @click="openWalletModal(user, 'deduct')" class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all" title="Deduct Wallet">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="h-6 w-px bg-slate-200 mx-1"></div>
                                        <button @click="openEditModal(user)" class="p-2.5 text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-xl transition-all" title="Edit Profile">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="confirmDelete(user)" class="p-2.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all" title="Delete User">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="users.length === 0">
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="max-w-xs mx-auto">
                                    <div class="w-16 h-16 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 mx-auto mb-3">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                    <p class="text-slate-900 font-semibold">No users found</p>
                                    <p class="text-slate-500 text-sm mt-1">Adjust search or role filter.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-slate-500">
                    Showing <span x-text="(pagination.current_page - 1) * pagination.per_page + 1"></span>–<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of <span x-text="pagination.total"></span>
                </p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="btn-admin-secondary px-4 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                    <span class="text-sm text-slate-600" x-text="'Page ' + pagination.current_page + ' of ' + (pagination.last_page || 1)"></span>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="btn-admin-secondary px-4 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                    <button @click="fetchUsers()" class="p-2 text-slate-400 hover:text-cyan-600 rounded-xl" title="Refresh"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS -->

    <!-- Add/Edit User Modal -->
    <div x-show="showUserModal" x-cloak class="fixed inset-0 z-[200] overflow-y-auto" role="dialog" aria-modal="true">
        <div x-show="showUserModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showUserModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="showUserModal" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95" class="relative bg-white w-full max-w-lg rounded-2xl shadow-3xl overflow-hidden border border-slate-100 p-10">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-2xl font-black text-slate-900" x-text="isEditing ? 'Editing Profile' : 'Add New User'"></h3>
                    <button @click="showUserModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="saveUser()" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-2">Full Name</label>
                            <input type="text" x-model="formData.full_name" required class="w-full px-6 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-500 outline-none transition-all font-bold text-slate-900">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-2">Email Address</label>
                                <input type="email" x-model="formData.email" required class="w-full px-6 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-500 outline-none transition-all font-bold text-slate-900">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-2">Phone Number</label>
                                <input type="tel" x-model="formData.phone" required class="w-full px-6 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-500 outline-none transition-all font-bold text-slate-900">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-2">Platform Role</label>
                                <select x-model="formData.role" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-500 outline-none transition-all font-bold text-slate-900">
                                    <option value="user">Standard User</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div x-show="!isEditing">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-2">Starting Balance</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₵</span>
                                    <input type="number" step="0.01" x-model="formData.balance" class="w-full pl-8 pr-6 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-500 outline-none transition-all font-bold text-slate-900">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-2" x-text="isEditing ? 'New Password (Optional)' : 'Password'"></label>
                            <input type="password" x-model="formData.password" :required="!isEditing" placeholder="••••••••" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-500 outline-none transition-all font-bold text-slate-900">
                        </div>
                    </div>

                    <div class="pt-6 flex space-x-4">
                        <button type="button" @click="showUserModal = false" class="flex-1 py-4 px-6 rounded-2xl border border-slate-200 text-slate-600 font-black hover:bg-slate-50 transition-all">Cancel</button>
                        <button type="submit" class="flex-[2] py-4 px-6 rounded-xl bg-cyan-600 text-white font-semibold hover:bg-cyan-700 active:scale-95 transition-all">
                            <span x-text="isEditing ? 'Save Changes' : 'Create User'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Wallet Modal (Topup/Deduct) -->
    <div x-show="showWalletModal" x-cloak class="fixed inset-0 z-[200] overflow-y-auto" role="dialog" aria-modal="true">
        <div x-show="showWalletModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showWalletModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="showWalletModal" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95" class="relative bg-white w-full max-w-sm rounded-2xl shadow-3xl overflow-hidden border border-slate-100 p-10 text-center">
                
                <div :class="walletData.type === 'topup' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'" 
                     class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg x-show="walletData.type === 'topup'" class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"/></svg>
                    <svg x-show="walletData.type === 'deduct'" class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                </div>

                <h3 class="text-2xl font-black text-slate-900 mb-2" x-text="walletData.type === 'topup' ? 'Topup Wallet' : 'Deduct Wallet'"></h3>
                <p class="text-slate-500 font-medium mb-8">User: <span class="font-black text-slate-900" x-text="selectedUser?.full_name"></span></p>

                <form @submit.prevent="processWallet()" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Amount (GHS)</label>
                        <input type="number" step="0.01" required x-model="walletData.amount" placeholder="0.00" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-500 outline-none transition-all font-black text-center text-2xl text-slate-900">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Reason / Description</label>
                        <input type="text" x-model="walletData.reason" placeholder="e.g. Compensation for lag" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-500 outline-none transition-all font-bold text-slate-900">
                    </div>

                    <div class="pt-6 space-y-3">
                        <button type="submit" :class="walletData.type === 'topup' ? 'bg-green-600 shadow-green-500/20' : 'bg-red-600 shadow-red-500/20'" 
                                class="w-full py-4 px-6 rounded-2xl text-white font-black hover:opacity-90 shadow-lg active:scale-95 transition-all">
                            Confirm Transaction
                        </button>
                        <button type="button" @click="showWalletModal = false" class="w-full py-2 text-slate-400 hover:text-slate-600 font-bold transition-colors">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[200] overflow-y-auto" role="dialog" aria-modal="true">
        <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showDeleteModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95" class="relative bg-white w-full max-w-sm rounded-2xl shadow-3xl overflow-hidden border border-slate-100 p-10 text-center">
                
                <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>

                <h3 class="text-2xl font-black text-slate-900 mb-2">Delete Account?</h3>
                <p class="text-slate-500 font-medium mb-8 leading-relaxed">You're about to remove <span class="text-slate-900 font-black" x-text="selectedUser?.full_name"></span>. This will purge their transaction history and is irreversible.</p>

                <div class="space-y-3">
                    <button @click="deleteUser()" class="w-full bg-red-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-700 active:scale-95 transition-all">Confirm Deletion</button>
                    <button @click="showDeleteModal = false" class="w-full text-slate-400 hover:text-slate-600 font-bold py-2 transition-colors">Go Back</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function userManagement() {
    return {
        users: [],
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
        search: '',
        roleFilter: 'all',
        sortField: 'created_at',
        sortOrder: 'desc',
        
        // Modal States
        showUserModal: false,
        showWalletModal: false,
        showDeleteModal: false,
        
        // Data States
        isEditing: false,
        selectedUser: null,
        formData: {
            full_name: '',
            email: '',
            phone: '',
            role: 'user',
            balance: 0,
            password: ''
        },
        walletData: {
            amount: '',
            type: 'topup',
            reason: ''
        },

        async init() {
            if(!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchUsers();
        },

        buildUsersParams(page) {
            const p = new URLSearchParams();
            if (page != null) p.set('page', page);
            p.set('per_page', this.pagination.per_page);
            if (this.search && this.search.trim()) p.set('search', this.search.trim());
            if (this.roleFilter && this.roleFilter !== 'all') p.set('role', this.roleFilter);
            p.set('sort_by', this.sortField);
            p.set('sort_dir', this.sortOrder);
            return p.toString();
        },
        async fetchUsers(page) {
            if (page != null) this.pagination.current_page = page;
            try {
                const response = await fetch('/api/admin/users?' + this.buildUsersParams(this.pagination.current_page), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if(!response.ok) throw new Error('Failed to fetch users');
                const data = await response.json();
                this.users = data.data !== undefined ? data.data : data;
                if (data.current_page != null) {
                    this.pagination.current_page = data.current_page;
                    this.pagination.last_page = data.last_page ?? 1;
                    this.pagination.total = data.total ?? this.users.length;
                    this.pagination.per_page = data.per_page ?? 20;
                }
            } catch (e) {
                console.error(e);
                alert('Connection error while fetching users.');
            }
        },
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.fetchUsers(page);
        },
        sortBy(field) {
            if (this.sortField === field) this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            else { this.sortField = field; this.sortOrder = 'desc'; }
            this.fetchUsers(1);
        },
        async exportCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.search && this.search.trim()) params.set('search', this.search.trim());
                if (this.roleFilter && this.roleFilter !== 'all') params.set('role', this.roleFilter);
                params.set('sort_by', this.sortField);
                params.set('sort_dir', this.sortOrder);
                const response = await fetch('/api/admin/users?' + params.toString(), { headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' } });
                if (!response.ok) throw new Error('Export failed');
                const data = await response.json();
                const list = data.data || data;
                if (!list.length) { alert('No users to export.'); return; }
                const headers = ['ID', 'Full Name', 'Email', 'Phone', 'Role', 'Balance', 'Created'];
                const escape = (v) => '"' + String(v).replace(/"/g, '""') + '"';
                const rows = list.map(u => [u.id, u.full_name || '', u.email || '', u.phone || '', u.role || '', u.balance != null ? u.balance : '', u.created_at ? new Date(u.created_at).toLocaleString() : '']);
                const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(r => r.map(escape).join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", "users_export_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) { console.error(e); alert('Export failed.'); }
        },

        openAddModal() {
            this.isEditing = false;
            this.formData = { full_name: '', email: '', phone: '', role: 'user', balance: 0, password: '' };
            this.showUserModal = true;
        },

        openEditModal(user) {
            this.isEditing = true;
            this.selectedUser = user;
            this.formData = { 
                full_name: user.full_name, 
                email: user.email, 
                phone: user.phone, 
                role: user.role, 
                password: '' 
            };
            this.showUserModal = true;
        },

        async saveUser() {
            const url = this.isEditing ? `/api/admin/users/${this.selectedUser.id}` : '/api/admin/users';
            const method = this.isEditing ? 'PUT' : 'POST';
            
            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });
                
                const data = await response.json();
                if(response.ok) {
                    alert(this.isEditing ? 'User updated successfully!' : 'User created successfully!');
                    this.showUserModal = false;
                    await this.fetchUsers();
                } else {
                    alert(Object.values(data).flat().join('\n'));
                }
            } catch (e) {
                alert('An error occurred while saving.');
            }
        },

        openWalletModal(user, type) {
            this.selectedUser = user;
            this.walletData = { amount: '', type, reason: '' };
            this.showWalletModal = true;
        },

        async processWallet() {
            const amount = parseFloat(this.walletData.amount);
            if (isNaN(amount) || amount < 0.01) {
                alert('Please enter a valid amount (at least 0.01).');
                return;
            }
            try {
                const payload = {
                    amount: amount,
                    type: this.walletData.type,
                    reason: this.walletData.reason || null
                };
                const response = await fetch(`/api/admin/users/${this.selectedUser.id}/balance`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json().catch(() => ({}));
                
                if (response.ok) {
                    alert('Wallet adjusted successfully!');
                    this.showWalletModal = false;
                    await this.fetchUsers();
                } else {
                    const msg = data.error || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to adjust balance');
                    alert(msg);
                }
            } catch (e) {
                console.error(e);
                alert('Network error. Please try again.');
            }
        },

        confirmDelete(user) {
            this.selectedUser = user;
            this.showDeleteModal = true;
        },

        async deleteUser() {
            try {
                const response = await fetch(`/api/admin/users/${this.selectedUser.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if(response.ok) {
                    alert('User removed from system.');
                    this.showDeleteModal = false;
                    await this.fetchUsers();
                } else {
                    const data = await response.json();
                    alert(data.error || 'Failed to delete user');
                }
            } catch (e) {
                alert('Deletion failed.');
            }
        }
    }
}
</script>
@endsection
