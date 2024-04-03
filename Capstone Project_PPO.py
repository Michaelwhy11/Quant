#!/usr/bin/env python
# coding: utf-8

# # Setup

# !git clone https://github.com/jpmorganchase/abides-jpmc-public

# cd /opt/anaconda3/lib/python3.9/site-packages/abides-jpmc-public

# cd /opt/anaconda3/envs/Capstone/lib/python3.8/abides-jpmc-public

# In[16]:


import ray
from ray import tune


# In[17]:


help(ray.tune.run)


# !sh install.sh

# Install in a new environment:
# 
# conda install -n myenv pip
# 
# conda activate myenv
# 
# pip <pip_subcommand>
# 
# (Same for sh)
# 
# Resources: https://conda.io/projects/conda/en/latest/user-guide/tasks/manage-environments.html

# from platform import python_version
# 
# print(python_version())

# # Usage

# In[1]:


import warnings
warnings.filterwarnings("ignore")


# In[26]:


from abides_markets.configs import rmsc04
from abides_core import abides

config_state=rmsc04.build_config(seed=0,end_time='10:00:00')
end_state=abides.run(config_state)


# In[27]:


import gym
import abides_gym


# In[28]:


env=gym.make("markets-daily_investor-v0", #markets_daily_investor_environment_v0
             background_config="rmsc04"
     )

env.seed(0)


# In[29]:


state=env.reset()


# In[30]:


print(state)


# In[31]:


env.action_space


# # Step in the Simulation

# In[8]:


state,reward,done,info = env.step(0)


# In[9]:


print('State:')
print(state)
print('-------------------------------------')
print('Reward:')
print(reward)
print('-------------------------------------')
print('Done:')
print(done)
print('-------------------------------------')
print('Info:')
print(info)


# # Training an RL algorithm in ABIDES

# !pip install ray --upgrade

# In[24]:


import ray
from ray import tune

import abides_gym

from ray.tune.registry import register_env

from abides_gym.envs.markets_daily_investor_environment_v0 import (SubGymMarketsDailyInvestorEnv_v0,)


# !pip install tensorflow

# In[25]:


import tensorflow
import tensorboard


# !pip install tensorflow_probability
# import tensorflow_probability
# import numpy as np
# print(np.__version__)
# 
# !pip install --upgrade pip
# !pip install --upgrade pyproject.toml
# !python --version
# !pip uninstall numpy
# !pip install numpy==1.19.1
# 
# !pip3 install --upgrade numpy==1.19.1

# !pip3 install torch torchvision torchaudio

# execute_cell_flag=True
# 
# if execute_cell_flag:
#     ray.shutdown()
#     ray.init()
#     
#     register_env("markets-daily_investor-v0",
#                  lambda config:SubGymMarketsDailyInvestorEnv_v0(**config), )
#     
#     name_xp='dqn_market_run100_2'
#     
#     tune.run("DQN",#DQN
#              resume=True,
#              name=name_xp,
#              resume=False,
#              stop={'training_iteration':100},
#              checkpoint_at_end=True,
#              checkpoint_freq=5,
#              config={'env':"markets-daily_investor-v0",
#                      'env_config':{'background_config':'rmsc04',
#                                    'timestep_duration':"10S",
#                                    'mkt_close':"16:00:00",
#                                    'timestep_duration':"60s",
#                                    'starting_cash': 1_000_000,
#                                    'order_fixed_size': 10,
#                                    'state_history_length':4,
#                                    'market_data_buffer_length': 5,
#                                    'first_interval': "00:05:00",
#                                    'reward_mode': "dense",
#                                    'done_ratio': 0.3,
#                                    'debug_mode': True,
#                                    #'execution_window':'04:00:00',
#                                    #.... Here we just use the default values
#                                    },
#                      'seed':tune.grid_search([1]),
#              #'seed':tune.grid_search([1,2,3]),
#                      'num_gpus':0,
#                      'num_workers':0,
#                      'hiddens':[50,20],
#                      'gamma':1,
#                      'lr':tune.grid_search([0.001]),
#              #'lr':tune.grid_search([0.001,0.0001,0.01]),
#                      'framework':'torch',
#                      'observation_filter':'MeanStdFilter',
#              },
#              )

# In[14]:


execute_cell_flag=True

if execute_cell_flag:
    ray.shutdown()
    ray.init()
    
    register_env("markets-daily_investor-v0",
                 lambda config:SubGymMarketsDailyInvestorEnv_v0(**config), )
    
    name_xp='ppo_market_run100_1'
    
    tune.run("PPO",#DQN
             name=name_xp,
             resume=True,
             stop={'training_iteration':100},
             checkpoint_at_end=True,
             checkpoint_freq=5,
             config={'env':"markets-daily_investor-v0",
                     'env_config':{'background_config':'rmsc04',
                                   'timestep_duration':"10S",
                                   'mkt_close':"16:00:00",
                                   'timestep_duration':"60s",
                                   'starting_cash': 1_000_000,
                                   'order_fixed_size': 10,
                                   'state_history_length':4,
                                   'market_data_buffer_length': 5,
                                   'first_interval': "00:05:00",
                                   'reward_mode': "dense",
                                   'done_ratio': 0.3,
                                   'debug_mode': True,
                                   #'execution_window':'04:00:00',
                                   #.... Here we just use the default values
                                   },
                     'seed':tune.grid_search([1]),
             #'seed':tune.grid_search([1,2,3]),
                     #'num_gpus':0,
                     #'num_workers':0,
                     #'hiddens':[50,20],
                     'gamma':1,
                     'lr':tune.grid_search([0.001]),
             #'lr':tune.grid_search([0.001,0.0001,0.01]),
                     'framework':'torch',
                     #'observation_filter':'MeanStdFilter',
             },
             )


# !pip install tensorboard

# !pip install tensorflow

# In[1]:


get_ipython().system('tensorboard --logdir=~/ray_results/{name_xp}')


# tensorboard --logdir=~/ray_results/dpn_market_demo_3

# # Define or load a policy

# Note:
# 
# Ray Library Issue Link:https://github.com/ray-project/ray/issues/28518

# In[10]:


import numpy as np
np.random.seed(0)


# In[14]:


import ray.rllib.agents.dqn as dqn
import ray.rllib.agents.ppo as ppo

from ray.tune import Analysis


# In[15]:


class policyPassive:
    def __init__(self):
        self.name = 'passive'
        
    def get_action(self, state):
        return 1
        
class policyAggressive:
    def __init__(self):
        self.name = 'aggressive'
        
    def get_action(self, state):
        return 0
    
class policyRandom:
    def __init__(self):
        self.name = 'random'
        
    def get_action(self, state):
        return np.random.choice([0,1])
    
class policyRandomWithNoAction:
    def __init__(self):
        self.name = 'random_no_action'
        
    def get_action(self, state):
        return np.random.choice([0,1, 2])


# In[41]:


class policyRL:
    def __init__(self):
        self.name='r1'
        name_xp='PPO_markets-daily_investor-v0_b5e34_00000_0_lr=0.001,seed=1_2023-04-12_15-16-45'
        
        data_folder=f"~/ray_results/ppo_market_run100_1/{name_xp}"
        analysis=Analysis(data_folder)
        trail_dataframes=analysis.trial_dataframes
        trials=list(trail_dataframes.keys())
        best_trial_path=analysis.get_best_logdir(metric='episode_reward_mean',mode='max')
        best_checkpoint=analysis.get_best_checkpoint(trial=best_trial_path,mode='max')
        #best_checkpoint=complex(best_checkpoint)
        
        config=ppo.DEFAULT_CONFIG.copy()
        config['framework']='torch'
        #config['observation_filter']='MeanStdFilter'
        #config['hiddens']=[50,20]
        config['env_config']={'background_config':'rmsc04',
                              'timestep_duration':'10S',
                              'debug_mode':True
                              #Others
                             }
        self.trainer=ppo.PPOTrainer(config=config,env='markets-daily_investor-v0')
        self.trainer.restore(best_checkpoint)
    
    def get_action(self,state):
        return self.trainer.compute_action(state)      


# In[17]:


def generate_env(seed):
    env=gym.make('markets-daily_investor-v0',background_config='rmsc04',
                 timestep_duration='10S',debug_mode=True)
    env.seed(seed)
    return env


# In[18]:


from collections.abc import MutableMapping
import pandas as pd

def flatten_dict(d:MutableMapping,sep:str='.') -> MutableMapping:
    [flat_dict]=pd.json_normalize(d,sep=sep).to_dict(orient='records')
    return flat_dict


# In[19]:


def run_episode(seed=None,policy=None):
    env=generate_env(seed)
    state=env.reset()
    done=False
    episode_reward=0
    
    while not done:
        action=policy.get_action(state)
        state,reward,done,info=env.step(action)
        episode_reward += reward
    
    output=flatten_dict(info)
    output['episode_reward']=episode_reward
    output['name']=policy.name
    return output


# import p_tqdm import p_map
# import pandas as pd
# from multiprocessing import Pool
# from pathos.multiprocessing import ProcessingPool
# 
# def run_N_episode(N):
#     policies=[policyAggressiove]

# In[36]:


N=1


# In[37]:


from p_tqdm import p_map
import pandas as pd 
from multiprocessing import Pool
from pathos.multiprocessing import ProcessingPool as Pool


# In[38]:


def run_N_episode(N):
    """
    run in parallel N episode of testing for the different policies defined in policies list
    heads-up: does not work yet for rllib policies - pickle error
    #https://stackoverflow.com/questions/28821910/how-to-get-around-the-pickling-error-of-python-multiprocessing-without-being-in

    need to run the rllib policies with following cell (not in parralel)
    
    """
    #define policies 
    policies = [policyAggressive(), policyRandom(), policyPassive(), policyRandomWithNoAction()]
    seeds = [i for i in range(N)]
    
#     tests = [{"policy":policy, 'seed':seed} for policy in policies for seed in seeds]
    
#     def wrap_run_episode(param):
#         return run_episode(**param)
    
#     outputs = p_map(wrap_run_episode, tests)
    #outputs = Pool().map(wrap_run_episode, tests)
    outputs = []
    for policy in policies:
        for seed in seeds:
            outputs.append(run_episode(seed=seed, policy=policy))
    
    return outputs


# In[39]:


outputs2 = run_N_episode(N)


# In[ ]:





# In[42]:


from tqdm import tqdm
# outputs=[run_episode(seed=0,policy=policyRL())]
for i in tqdm(range(N)):
    outputs2.append(run_episode(seed=i,policy=policyRL()))


# from tqdm import tqdm
# outputs=run_episode(seed=0,policy=policyRL())
# for i in tqdm(range(1,N)):
#     outputs.append(run_episode(seed=i,policy=policyRL()))

# # Result

# In[43]:


df2 = pd.DataFrame(outputs2)
df2.head()


# In[ ]:


df3=df2[['name','episode_reward', 'cash', 'holdings', 'spread','marked_to_market']]


# In[ ]:


df3.to_excel('N_50_results.xlsx')


# !pip install openpyxl

# In[45]:


#group by policy name and sort
df_g2 = df2.groupby(by='name').mean()

list_interest =  ['episode_reward', 'cash', 'holdings', 'spread','marked_to_market']

df_g2.sort_values(by = 'episode_reward', ascending=False)[list_interest]


# df_g = df.groupby(by='name').mean()
# 
# list_interest =  ['episode_reward', 'cash', 'holdings', 'spread','marked_to_market']
# 
# df_g.sort_values(by = 'episode_reward', ascending=False)[list_interest]

# In[86]:


df_g2['profit_percentage']=(df_g2['marked_to_market']-1000000.0)/1000000.0*100


# df_g2[['profit','profit_percentage']]

# In[ ]:


import matplotlib.pyplot as plt
get_ipython().run_line_magic('matplotlib', 'inline')
import seaborn as sns


# In[ ]:


#distribution plots
for metric in list_interest:
    print(f'======================={metric}========================')
    sns.displot(data = df2, x=metric, hue='name', bins=10)
    plt.show()
    sns.displot(data = df2, x=metric, hue='name',  kind="kde")
    plt.show()


# # Data Simulation

# import numpy as np
# import pandas as pd
# from matplotlib import pyplot as plt
# 
# from abides_core import abides
# from abides_core.utils import parse_logs_df, ns_date, str_to_ns, fmt_ts
# from abides_markets.configs import rmsc04
# 
# 
# config = rmsc04.build_config()
# end_state = abides.run( config )

# order_book = end_state["agents"][0].order_books["ABM"]

# order_book

# In[ ]:




