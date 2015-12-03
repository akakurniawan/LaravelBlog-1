<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Requests\UserRequest;
use App\Repositories\UserRepository;

class UserController extends Controller
{
    protected $user;

    public function __construct(UserRepository $users)
    {
        // $this->middleware('auth', ['except' => ['index', 'show', 'forUser']]);
        $this->users = $users;
    }

    public function index()
    {
        $users = User::all();
        return view('users.index');
    }

    public function show(User $user)
    {
        return view('users.user', compact('user'));
    }

    public function articles($id)
    {
        $user = User::findOrFail($id);
        $articles = $user->articles()->actived()->recent()->simplePaginate(10);
        return view('articles.user', compact('user', 'articles'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(User $user, UserRequest $request)
    {
        dd($user->toArray());
        $user->update($request->all());
        flash()->message('修改成功！');
        return redirect('user/' . $user->id . '/edit');
    }
}
