<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\History;
use App\Notify;
use Auth, Validator, Session;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Requests\UserRequest;
use App\Repositories\UserRepository;

class UserController extends Controller
{
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->middleware('auth', ['only' => ['edit', 'update', 'trash', 'resetpwd', 'updatepwd', 'follow', 'notifications']]);
        $this->users = $users;
        view()->share('currentUser', Auth::user());
    }

    public function index()
    {
        $users = User::latest()->get();
        return view('users.index', compact('users'));
    }

    public function show(User $user)
    {
        $histories = $user->histories()->latest()->simplePaginate(10);
        return view('users.user', compact('user', 'histories'));
    }

    public function articles($id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();
        if($currentUser && ($currentUser->id == $id || $currentUser->can('article.manage')))
            $articles = $user->articles()->latest()->simplePaginate(10);
        else
            $articles = $user->articles()->latest()->simplePaginate(10);
        return view('users.articles', compact('user', 'articles'));
    }

    public function trash($id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();
        if ($user->id != $currentUser->id) {
            return redirect('/');
        }
        $articles = $user->articles()->onlyTrashed()->latest()->simplePaginate(10);
        return view('users.trash', compact('user', 'articles'));
    }

    public function collects($id)
    {
        $user = User::findOrFail($id);
        $articles = $user->collects()->latest()->simplePaginate(10);
        return view('users.collects', compact('user', 'articles'));
    }

    public function follows($id)
    {
        $user = User::findOrFail($id);
        $follows = $user->follows()->latest()->simplePaginate(24);
        return view('users.follows', compact('user', 'follows'));
    }

    public function fans($id)
    {
        $user = User::findOrFail($id);
        $fans = $user->fans()->latest()->simplePaginate(24);
        return view('users.fans', compact('user', 'fans'));
    }

    public function notifications($id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();
        if ($user->id != $currentUser->id) {
            return redirect('/');
        }
        $notifications = $user->notifications()->orWhere('to_all', 1)->latest()->simplePaginate(10);
        $notifications = $user->notifications()->orWhere(function($query) use ($currentUser){
                $query->where('to_all', 1)->where('created_at', '>', $currentUser->created_at);
            })->latest()->simplePaginate(10);
        $notice_count = $user->notice_count;
        // Session::flash('notice_count', $notice_count);
        $user->decrement('notice_count', $notice_count);
        $count = $notice_count - ($notifications->currentPage() - 1) * $notifications->perPage();
        $count = $count > 0 ? $count : 0;
        return view('users.notifications', compact('user', 'notifications', 'notice_count', 'count'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(User $user, UserRequest $request)
    {
        $this->authorize('update', $user);
        $data = $request->only(['nickname', 'website', 'weibo', 'qq', 'github', 'description']);
        $user->update($data);
        flash()->message('修改成功！');
        return redirect('user/' . $user->id . '/edit');
    }

    public function resetpwd($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        return view('users.resetpwd', compact('user'));
    }

    public function updatepwd($id, UserRequest $request)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $validation = Validator::make($request->all(), [
            'password' => 'required|min:6',
            'password_new' => 'required|confirmed|min:6',
        ]);
        if ($validation->fails()){
            return redirect()->back()->withErrors($validation);
        }
        if (! \Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['notmatch' => '初始密码不正确']);
        }
        $user->password = bcrypt($request->password_new);
        $user->save();
        flash()->message('修改成功！');
        return redirect()->back();
    }

    public function follow(Request $request)
    {
        $user = User::find($request->id);
        if(empty($user)){
            return response()->json(404);
        }

        return response()->json($this->users->follow($user));
    }
}
