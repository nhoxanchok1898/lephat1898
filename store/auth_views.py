from django.shortcuts import render, redirect
from django.contrib.auth import login, logout
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm
from django.contrib.auth.decorators import login_required


def register_view(request):
	"""Simple user registration view using Django's UserCreationForm."""
	if request.method == 'POST':
		form = UserCreationForm(request.POST)
		if form.is_valid():
			user = form.save()
			login(request, user)
			return redirect('store:home')
	else:
		form = UserCreationForm()
	return render(request, 'auth/register.html', {'form': form})


def login_view(request):
	"""Basic login view wrapper around Django's AuthenticationForm."""
	if request.method == 'POST':
		form = AuthenticationForm(request, data=request.POST)
		if form.is_valid():
			user = form.get_user()
			login(request, user)
			return redirect('store:home')
	else:
		form = AuthenticationForm()
	return render(request, 'auth/login.html', {'form': form})


def logout_view(request):
	logout(request)
	return redirect('store:home')


@login_required
def profile_view(request):
	return render(request, 'auth/profile.html')
