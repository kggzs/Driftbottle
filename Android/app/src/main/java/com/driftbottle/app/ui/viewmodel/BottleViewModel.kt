package com.driftbottle.app.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.driftbottle.app.data.repository.BottleRepository
import com.driftbottle.app.data.model.response.BottleResponse
import com.driftbottle.app.data.model.response.CommentResponse
import com.driftbottle.app.domain.usecase.CreateBottleUseCase
import com.driftbottle.app.domain.usecase.PickBottleUseCase
import com.driftbottle.app.data.model.response.CreateBottleResponse
import com.driftbottle.app.data.model.response.PickBottleResponse
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class BottleViewModel @Inject constructor(
    private val createBottleUseCase: CreateBottleUseCase,
    private val pickBottleUseCase: PickBottleUseCase,
    private val bottleRepository: BottleRepository
) : ViewModel() {
    
    private val _throwBottleUiState = MutableStateFlow<ThrowBottleUiState>(ThrowBottleUiState.Idle)
    val throwBottleUiState: StateFlow<ThrowBottleUiState> = _throwBottleUiState.asStateFlow()
    
    private val _pickBottleUiState = MutableStateFlow<PickBottleUiState>(PickBottleUiState.Idle)
    val pickBottleUiState: StateFlow<PickBottleUiState> = _pickBottleUiState.asStateFlow()
    
    private val _bottleDetailUiState = MutableStateFlow<BottleDetailUiState>(BottleDetailUiState.Idle)
    val bottleDetailUiState: StateFlow<BottleDetailUiState> = _bottleDetailUiState.asStateFlow()
    
    private val _userBottlesUiState = MutableStateFlow<UserBottlesUiState>(UserBottlesUiState.Idle)
    val userBottlesUiState: StateFlow<UserBottlesUiState> = _userBottlesUiState.asStateFlow()
    
    private val _addCommentUiState = MutableStateFlow<AddCommentUiState>(AddCommentUiState.Idle)
    val addCommentUiState: StateFlow<AddCommentUiState> = _addCommentUiState.asStateFlow()
    
    private val _likeBottleUiState = MutableStateFlow<LikeBottleUiState>(LikeBottleUiState.Idle)
    val likeBottleUiState: StateFlow<LikeBottleUiState> = _likeBottleUiState.asStateFlow()
    
    fun createBottle(
        content: String,
        isAnonymous: Boolean,
        bottleType: String = "text",
        audioFile: String? = null,
        audioDuration: Int? = null
    ) {
        viewModelScope.launch {
            _throwBottleUiState.value = ThrowBottleUiState.Loading
            
            createBottleUseCase(content, isAnonymous, bottleType, audioFile, audioDuration)
                .onSuccess {
                    _throwBottleUiState.value = ThrowBottleUiState.Success(it)
                }
                .onFailure { error ->
                    _throwBottleUiState.value = ThrowBottleUiState.Error(error.message ?: "创建漂流瓶失败")
                }
        }
    }
    
    fun pickBottle() {
        viewModelScope.launch {
            _pickBottleUiState.value = PickBottleUiState.Loading
            
            pickBottleUseCase()
                .onSuccess {
                    _pickBottleUiState.value = PickBottleUiState.Success(it)
                }
                .onFailure { error ->
                    _pickBottleUiState.value = PickBottleUiState.Error(error.message ?: "捡漂流瓶失败")
                }
        }
    }
    
    fun loadBottleDetail(bottleId: Int) {
        viewModelScope.launch {
            _bottleDetailUiState.value = BottleDetailUiState.Loading
            
            bottleRepository.getBottleDetail(bottleId)
                .onSuccess {
                    _bottleDetailUiState.value = BottleDetailUiState.Success(it)
                }
                .onFailure { error ->
                    _bottleDetailUiState.value = BottleDetailUiState.Error(error.message ?: "加载瓶子详情失败")
                }
        }
    }
    
    fun loadMyBottles() {
        viewModelScope.launch {
            _userBottlesUiState.value = UserBottlesUiState.Loading
            
            bottleRepository.getMyBottles()
                .onSuccess {
                    _userBottlesUiState.value = UserBottlesUiState.Success(it)
                }
                .onFailure { error ->
                    _userBottlesUiState.value = UserBottlesUiState.Error(error.message ?: "加载我的瓶子失败")
                }
        }
    }
    
    fun loadPickedBottles() {
        viewModelScope.launch {
            _userBottlesUiState.value = UserBottlesUiState.Loading
            
            bottleRepository.getPickedBottles()
                .onSuccess {
                    _userBottlesUiState.value = UserBottlesUiState.Success(it)
                }
                .onFailure { error ->
                    _userBottlesUiState.value = UserBottlesUiState.Error(error.message ?: "加载我捡到的瓶子失败")
                }
        }
    }
    
    fun addComment(bottleId: Int, content: String) {
        viewModelScope.launch {
            _addCommentUiState.value = AddCommentUiState.Loading
            
            bottleRepository.addComment(bottleId, content)
                .onSuccess {
                    _addCommentUiState.value = AddCommentUiState.Success(it)
                }
                .onFailure { error ->
                    _addCommentUiState.value = AddCommentUiState.Error(error.message ?: "添加评论失败")
                }
        }
    }
    
    fun likeBottle(bottleId: Int) {
        viewModelScope.launch {
            _likeBottleUiState.value = LikeBottleUiState.Loading
            
            bottleRepository.likeBottle(bottleId)
                .onSuccess {
                    _likeBottleUiState.value = LikeBottleUiState.Success(it)
                }
                .onFailure { error ->
                    _likeBottleUiState.value = LikeBottleUiState.Error(error.message ?: "点赞失败")
                }
        }
    }
}

sealed interface ThrowBottleUiState {
    object Idle : ThrowBottleUiState
    object Loading : ThrowBottleUiState
    data class Success(val response: CreateBottleResponse) : ThrowBottleUiState
    data class Error(val message: String) : ThrowBottleUiState
}

sealed interface PickBottleUiState {
    object Idle : PickBottleUiState
    object Loading : PickBottleUiState
    data class Success(val response: PickBottleResponse) : PickBottleUiState
    data class Error(val message: String) : PickBottleUiState
}

sealed interface BottleDetailUiState {
    object Idle : BottleDetailUiState
    object Loading : BottleDetailUiState
    data class Success(val response: BottleResponse) : BottleDetailUiState
    data class Error(val message: String) : BottleDetailUiState
}

sealed interface UserBottlesUiState {
    object Idle : UserBottlesUiState
    object Loading : UserBottlesUiState
    data class Success(val response: List<BottleResponse>) : UserBottlesUiState
    data class Error(val message: String) : UserBottlesUiState
}

sealed interface AddCommentUiState {
    object Idle : AddCommentUiState
    object Loading : AddCommentUiState
    data class Success(val response: CommentResponse) : AddCommentUiState
    data class Error(val message: String) : AddCommentUiState
}

sealed interface LikeBottleUiState {
    object Idle : LikeBottleUiState
    object Loading : LikeBottleUiState
    data class Success(val response: Any) : LikeBottleUiState
    data class Error(val message: String) : LikeBottleUiState
}
