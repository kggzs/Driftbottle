package com.driftbottle.app.ui.screens.bottle

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.driftbottle.app.R
import com.driftbottle.app.data.model.response.BottleResponse
import com.driftbottle.app.data.model.response.CommentResponse
import com.driftbottle.app.ui.viewmodel.BottleViewModel
import dagger.hilt.android.qualifiers.ApplicationContext

@Composable
fun BottleDetailScreen(
    bottleId: Int,
    onBack: () -> Unit
) {
    val viewModel = androidx.lifecycle.viewmodel.compose.viewModel<BottleViewModel>()
    val bottle by remember {
        mutableStateOf<BottleResponse?>(null)
    }
    val comments by remember {
        mutableStateOf<List<CommentResponse>>(emptyList())
    }
    val isLoading by remember {
        mutableStateOf(false)
    }
    val error by remember {
        mutableStateOf<String?>(null)
    }
    val commentText by remember {
        mutableStateOf("")
    }

    LaunchedEffect(bottleId) {
        // 这里应该调用viewModel加载瓶子详情
        // viewModel.loadBottleDetail(bottleId)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(stringResource(R.string.pick_bottle)) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(
                            androidx.compose.material.icons.Icons.Default.ArrowBack,
                            contentDescription = stringResource(R.string.back)
                        )
                    }
                }
            )
        }
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(it)
        ) {
            if (isLoading) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            } else if (error != null) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = error ?: stringResource(R.string.network_error),
                        color = MaterialTheme.colorScheme.error
                    )
                }
            } else if (bottle != null) {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp)
                ) {
                    item {
                        BottleContent(bottle = bottle!!)
                        Spacer(modifier = Modifier.height(24.dp))
                        Divider()
                        Spacer(modifier = Modifier.height(16.dp))
                        Text(
                            text = stringResource(R.string.comments),
                            style = MaterialTheme.typography.headlineSmall,
                            fontWeight = FontWeight.Bold
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                    }
                    if (comments.isEmpty()) {
                        item {
                            Text(
                                text = stringResource(R.string.no_comments),
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    } else {
                        items(comments) {
                            CommentItem(comment = it)
                            Spacer(modifier = Modifier.height(12.dp))
                        }
                    }
                    item {
                        Spacer(modifier = Modifier.height(80.dp))
                    }
                }
                CommentInput(
                    text = commentText,
                    onTextChange = { /*commentText = it*/ },
                    onSend = { /*viewModel.addComment(bottleId, it)*/ }
                )
            } else {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Text(stringResource(R.string.loading))
                }
            }
        }
    }
}

@Composable
fun BottleContent(bottle: BottleResponse) {
    Column {
        Row(
            modifier = Modifier.fillMaxWidth(),
            mainAxisAlignment = MainAxisAlignment.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = bottle.username ?: stringResource(R.string.anonymous),
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = bottle.created_at ?: "",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Spacer(modifier = Modifier.height(12.dp))
        Text(
            text = bottle.content ?: "",
            style = MaterialTheme.typography.bodyLarge
        )
        if (!bottle.audio_url.isNullOrEmpty()) {
            Spacer(modifier = Modifier.height(16.dp))
            // 这里应该添加音频播放控件
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(60.dp)
                    .background(MaterialTheme.colorScheme.surfaceVariant)
                    .padding(12.dp),
                contentAlignment = Alignment.Center
            ) {
                Text(stringResource(R.string.audio_bottle))
            }
        }
        Spacer(modifier = Modifier.height(16.dp))
        Row(
            modifier = Modifier.fillMaxWidth(),
            mainAxisAlignment = MainAxisAlignment.SpaceAround
        ) {
            IconButton(onClick = { /* 点赞 */ }) {
                Icon(
                    if (bottle.is_liked == true) {
                        androidx.compose.material.icons.Icons.Default.Favorite
                    } else {
                        androidx.compose.material.icons.Icons.Default.FavoriteBorder
                    },
                    contentDescription = stringResource(R.string.likes)
                )
            }
            IconButton(onClick = { /* 评论 */ }) {
                Icon(
                    androidx.compose.material.icons.Icons.Default.Comment,
                    contentDescription = stringResource(R.string.comments)
                )
            }
            IconButton(onClick = { /* 分享 */ }) {
                Icon(
                    androidx.compose.material.icons.Icons.Default.Share,
                    contentDescription = "分享"
                )
            }
        }
    }
}

@Composable
fun CommentItem(comment: CommentResponse) {
    Column {
        Row(
            modifier = Modifier.fillMaxWidth(),
            mainAxisAlignment = MainAxisAlignment.SpaceBetween
        ) {
            Text(
                text = comment.username ?: stringResource(R.string.anonymous),
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = comment.created_at ?: "",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Spacer(modifier = Modifier.height(4.dp))
        Text(
            text = comment.content ?: "",
            style = MaterialTheme.typography.bodyMedium
        )
    }
}

@Composable
fun CommentInput(
    text: String,
    onTextChange: (String) -> Unit,
    onSend: (String) -> Unit
) {
    Box(
        modifier = Modifier
            .fillMaxWidth()
            .padding(16.dp)
            .background(MaterialTheme.colorScheme.surface)
            .shadow(2.dp)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically
        ) {
            OutlinedTextField(
                value = text,
                onValueChange = onTextChange,
                placeholder = { Text(stringResource(R.string.write_your_mood)) },
                modifier = Modifier
                    .weight(1f)
                    .padding(end = 8.dp),
                singleLine = true
            )
            Button(onClick = { if (text.isNotBlank()) onSend(text) }) {
                Text(stringResource(R.string.send))
            }
        }
    }
}
