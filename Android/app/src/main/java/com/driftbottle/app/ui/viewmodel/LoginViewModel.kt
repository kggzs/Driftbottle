package com.driftbottle.app.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.driftbottle.app.domain.usecase.LoginUseCase
import com.driftbottle.app.data.model.response.LoginResponse
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val loginUseCase: LoginUseCase
) : ViewModel() {
    
    private val _uiState = MutableStateFlow<LoginUiState>(LoginUiState.Idle)
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()
    
    fun login(username: String, password: String) {
        viewModelScope.launch {
            _uiState.value = LoginUiState.Loading
            
            loginUseCase(username, password)
                .onSuccess {
                    _uiState.value = LoginUiState.Success(it)
                }
                .onFailure { error ->
                    _uiState.value = LoginUiState.Error(error.message ?: "登录失败")
                }
        }
    }
}

sealed interface LoginUiState {
    object Idle : LoginUiState
    object Loading : LoginUiState
    data class Success(val response: LoginResponse) : LoginUiState
    data class Error(val message: String) : LoginUiState
}
