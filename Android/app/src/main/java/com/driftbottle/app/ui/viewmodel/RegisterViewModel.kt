package com.driftbottle.app.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.driftbottle.app.domain.usecase.RegisterUseCase
import com.driftbottle.app.data.model.response.RegisterResponse
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RegisterViewModel @Inject constructor(
    private val registerUseCase: RegisterUseCase
) : ViewModel() {
    
    private val _uiState = MutableStateFlow<RegisterUiState>(RegisterUiState.Idle)
    val uiState: StateFlow<RegisterUiState> = _uiState.asStateFlow()
    
    fun register(username: String, password: String, gender: String) {
        viewModelScope.launch {
            _uiState.value = RegisterUiState.Loading
            
            registerUseCase(username, password, gender)
                .onSuccess {
                    _uiState.value = RegisterUiState.Success(it)
                }
                .onFailure { error ->
                    _uiState.value = RegisterUiState.Error(error.message ?: "注册失败")
                }
        }
    }
}

sealed interface RegisterUiState {
    object Idle : RegisterUiState
    object Loading : RegisterUiState
    data class Success(val response: RegisterResponse) : RegisterUiState
    data class Error(val message: String) : RegisterUiState
}
