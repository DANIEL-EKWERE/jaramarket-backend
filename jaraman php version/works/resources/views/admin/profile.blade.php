@extends('layouts.app')
@section('title','My Profile')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div><h1 class="page-title">My Profile</h1><p class="page-subtitle">Update your personal information and password</p></div>
    <form action="{{ route('admin.profile.update') }}" method="POST" class="card space-y-4">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label>First Name</label><input type="text" name="firstname" value="{{ old('firstname',$user->firstname) }}" required></div>
            <div><label>Last Name</label><input type="text" name="lastname" value="{{ old('lastname',$user->lastname) }}" required></div>
            <div><label>Email</label><input type="email" name="email" value="{{ old('email',$user->email) }}" required></div>
            <div><label>Phone Number</label><input type="text" name="phone_number" value="{{ old('phone_number',$user->phone_number) }}"></div>
        </div>
        <div class="border-t border-slate-100 pt-4">
            <p class="text-sm font-semibold text-slate-700 mb-3">Change Password</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label>Current Password</label><input type="password" name="current_password"></div>
                <div><label>New Password</label><input type="password" name="new_password"></div>
            </div>
        </div>
        <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl shadow-sm">Save Changes</button>
    </form>
</div>
@endsection
