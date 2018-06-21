# delay-queue
[![Downloads](https://img.shields.io/github/downloads/haozu/delay-queue/total.svg)](https://github.com/haozu/delay-queue/releases)
[![license](https://img.shields.io/github/license/mashape/apistatus.svg?maxAge=2592000)](https://github.com/haozu/delay-queue/blob/master/LICENSE)
[![Release](https://img.shields.io/github/release/haozu/delay-queue.svg?label=Release)](https://github.com/haozu/delay-queue/releases)

基于Redis实现的延迟队列,MsgPack编码数据 参考[有赞延迟队列设计](http://tech.youzan.com/queuing_delay)实现

## 应用场景
* 订单超过30天内未回款，通知处理
* 订单完成后, 如果未评价, 5天后自动好评
* 房租剩余15天, 到期前3天分别发送短信提醒续租等

## 支付宝异步通知实现
支付宝异步通知时间间隔是如何实现的(通知的间隔频率一般是：2m,10m,10m,1h,2h,6h,15h)  
 
订单支付成功后, 生成通知任务, 放入消息队列中.    
任务内容包含Array{0,0,2m,10m,10m,1h,2h,6h,15h}和通知到第几次N(这里N=1, 即第1次).    
消费者从队列中取出任务, 根据N取得对应的时间间隔为0, 立即发送通知.   

第1次通知失败, N += 1 => 2  
从Array中取得间隔时间为2m, 添加一个延迟时间为2m的任务到延迟队列, 任务内容仍包含Array和N     

第2次通知失败, N += 1 => 3, 取出对应的间隔时间10m, 添加一个任务到延迟队列, 同上   
......    
第7次通知失败, N += 1 => 8, 取出对应的间隔时间15h, 添加一个任务到延迟队列, 同上  
第8次通知失败, N += 1 => 9, 取不到间隔时间, 结束通知    


## 实现原理
> 利用Redis的有序集合，member为JobId, score为任务执行的时间戳,    
每秒扫描一次集合，取出执行时间小于等于当前时间的任务.   

## 依赖
* Redis MsgPack 扩展


## 下载
[releases](https://github.com/haozu/delay-queue/releases)

## composer安装

```bash
composer require haozu/delay-queue
```

### 添加任务 
```php
DelayQueue::enqueue('order','Job\\Order\\GetOrder',3,10,['id'=>8008]);
```

### 参数说明

|  参数名 |     类型    |     含义     |        备注       |
|:-------:|:-----------:|:------------:|:-----------------:|
|   topic  | string     |    一组相同类型Job的集合（队列）。                |        供消费者来订阅。               |
|   jobName  | string   |    job任务的类名，是延迟队列里的基本单元。                  |      与具体的Topic关联在一起。               |
|   delay  | int        |    Job需要延迟的时间, 单位：秒    |                   |
|   ttr    | int        |    Job执行超时时间, 单位：秒   |   保证job至少被消费一次获取job后超时未处理会重新投入队列    |
|   args   | string     |    Job的参数内容，供消费者做具体的业务处理， |        可选参数           |
|   id     | string     |    Job唯一标识                   | 需确保JobID唯一 可选参数          |

### 其他例子
* 包装例子   demo/MyDelayQueueDemo.php
* 添加例子   demo/AddDemo.php
* worker例子 demo/BackstageDemo.php.php
* job例子    demo/Job/JobDemo.php 
* 测试例子   test/DelayQueueTest.php