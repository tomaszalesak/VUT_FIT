#!/usr/bin/env python3

# ISJ practice No. 4
# Tomáš Zálešák, xzales13
# 29.3.2018

def can_be_a_set_member_or_frozenset(item):
    """Returns item or frozenset if it cannot be a set member"""

    # try to insert item to set and return item or frozen set according to exception
    try:
        test_set = { item }
    except TypeError:
        return frozenset(item)
    else:
        return item

def all_subsets(lst):
    """Returns list of all subsets of items in lst"""

    # check whether lst is None
    if lst is None:
        return None
    # list for return with empty set
    sublst = [[]]
    # empty set for loop of every item in lst
    next = []
    # append subsets to sublst list
    for l in lst:
        for s in sublst:
            next.append(s + [l])
        sublst += next
        next = []
    return sublst

def all_subsets_excl_empty(*lst, exclude_empty=None):
    """Returns list of all subsets of items in lst with option to exclude the empty subset"""

    # create list from passed parameters
    lst_ee = list(lst)
    # check exclude_empty parameter, call all_subsets function and delete [] item whether exlude_empty is None or True, return list
    if exclude_empty is None:
        lst_to_return = all_subsets(lst_ee)
        del lst_to_return[0]
        return lst_to_return
    elif exclude_empty == True:
        lst_to_return = all_subsets(lst_ee)
        del lst_to_return[0]
        return  lst_to_return
    elif exclude_empty == False:
        return all_subsets(lst_ee)
